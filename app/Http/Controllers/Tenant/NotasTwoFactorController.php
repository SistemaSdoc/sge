<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Notifications\Tenant\NotesAccessCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class NotasTwoFactorController extends Controller
{
    private const SESSION_KEY = 'notes_access.challenge';

    private const AUTHORIZATION_KEY = 'notes_access.authorizations';

    private const RETURN_TO_KEY = 'notes_access.return_to';

    private const CODE_LIFETIME_MINUTES = 10;

    private const RESEND_DELAY_SECONDS = 60;

    private const MAX_SENDS = 5;

    private const SEND_WINDOW_SECONDS = 900;

    private const MAX_ATTEMPTS = 5;

    private const SCOPES = ['discipline', 'history', 'pauta'];

    public function create(Request $request): Response|SymfonyResponse
    {
        $user = $this->authenticatedUser();
        $scope = $this->resolveScope($request->query('scope'));

        abort_if($scope === null, 404);

        $this->rememberRequestedDestination($request);

        if ($this->hasActiveAuthorization($request, $user, $scope)) {
            return $this->redirectToNotesDestination($request);
        }

        $sendError = null;
        $sendErrorType = null;

        try {
            $this->sendCodeUnlessActive($request, $user, $scope);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $sendErrorType = array_key_first($errors) === 'limit' ? 'limit' : 'send';
            $sendError = reset($errors)[0] ?? 'Não foi possível enviar o código.';
        }

        $challenge = $request->session()->get(self::SESSION_KEY);
        $challengeMatchesScope = is_array($challenge)
            && ($challenge['scope'] ?? null) === $scope
            && (string) ($challenge['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($challenge['tenant_id'] ?? '') === $this->tenantId();
        $hasActiveChallenge = $challengeMatchesScope
            && ($challenge['expires_at'] ?? 0) > now()->timestamp;
        $challengeExpired = $challengeMatchesScope
            && ($challenge['expires_at'] ?? 0) <= now()->timestamp;
        $limiterKey = $this->limiterKey($user);
        $canResendAt = $hasActiveChallenge
            ? (int) $challenge['sent_at'] + self::RESEND_DELAY_SECONDS
            : 0;

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS)) {
            $canResendAt = max($canResendAt, now()->timestamp + RateLimiter::availableIn($limiterKey));
        }

        return Inertia::render('tenant/auth/notes-two-factor-challenge', [
            'email' => $this->maskEmail($user->email),
            'canResendAt' => $canResendAt,
            'canVerify' => $hasActiveChallenge,
            'sendError' => $sendError,
            'sendErrorType' => $sendErrorType,
            'challengeExpired' => $challengeExpired,
            'scope' => $scope,
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser();
        $session = $request->session();
        $challenge = $session->get(self::SESSION_KEY);
        $requestedScope = $request->string('scope')->toString();
        $scope = $this->resolveScope($requestedScope !== ''
            ? $requestedScope
            : (is_array($challenge) ? ($challenge['scope'] ?? null) : null));

        if ($scope === null) {
            throw ValidationException::withMessages([
                'resend' => ['Não foi possível identificar a área. Abra novamente o acesso às notas.'],
            ]);
        }

        $challengeIsActive = is_array($challenge)
            && ($challenge['expires_at'] ?? 0) > now()->timestamp
            && ($challenge['scope'] ?? null) === $scope
            && (string) ($challenge['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($challenge['tenant_id'] ?? '') === $this->tenantId();
        $sentAt = $challengeIsActive ? (int) $challenge['sent_at'] : 0;

        if ($sentAt > now()->timestamp - self::RESEND_DELAY_SECONDS) {
            $waitSeconds = max(1, self::RESEND_DELAY_SECONDS - (now()->timestamp - $sentAt));

            throw ValidationException::withMessages([
                'resend' => ["Aguarde {$waitSeconds} segundos antes de pedir outro código."],
            ]);
        }

        $limiterKey = $this->limiterKey($user);

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS)) {
            throw ValidationException::withMessages([
                'resend' => ['O limite de cinco envios foi atingido. O contador indica quando poderá tentar novamente.'],
            ]);
        }

        $this->sendCode($request, $user, $scope);

        return back()->with('success', 'Um novo código foi enviado para o seu e-mail.');
    }

    public function verify(Request $request): SymfonyResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $session = $request->session();
        $challenge = $session->get(self::SESSION_KEY);

        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $session->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => ['O código expirou. Solicite um novo código.'],
            ]);
        }

        $scope = $this->resolveScope($challenge['scope'] ?? null);
        $user = $this->authenticatedUser();

        if ($scope === null
            || (string) ($challenge['user_id'] ?? '') !== (string) $user->getAuthIdentifier()
            || (string) ($challenge['tenant_id'] ?? '') !== $this->tenantId()) {
            $session->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => ['O código pertence a outra sessão. Solicite um novo código.'],
            ]);
        }

        $attempts = (int) ($challenge['attempts'] ?? 0) + 1;

        if ($attempts > self::MAX_ATTEMPTS) {
            $session->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => ['Limite de tentativas atingido. Solicite um novo código.'],
            ]);
        }

        $challenge['attempts'] = $attempts;
        $session->put(self::SESSION_KEY, $challenge);

        if (! Hash::check($validated['code'], $challenge['hash'])) {
            throw ValidationException::withMessages([
                'code' => ['O código informado está incorreto.'],
            ]);
        }

        $session->forget(self::SESSION_KEY);
        $session->put(self::AUTHORIZATION_KEY.'.'.$scope, [
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES)->timestamp,
            'user_id' => $user->getAuthIdentifier(),
            'tenant_id' => $this->tenantId(),
        ]);

        return $this->redirectToNotesDestination($request);
    }

    private function sendCodeUnlessActive(Request $request, User $user, string $scope): void
    {
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (is_array($challenge)
            && ($challenge['expires_at'] ?? 0) > now()->timestamp
            && ($challenge['scope'] ?? null) === $scope
            && (string) ($challenge['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($challenge['tenant_id'] ?? '') === $this->tenantId()) {
            return;
        }

        $this->sendCode($request, $user, $scope);
    }

    private function sendCode(Request $request, User $user, string $scope): void
    {
        if (blank($user->email)) {
            throw ValidationException::withMessages([
                'send' => ['A conta não possui um endereço de e-mail para receber o código.'],
            ]);
        }

        $limiterKey = $this->limiterKey($user);

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS)) {
            throw ValidationException::withMessages([
                'limit' => ['Atingiu o limite de cinco envios. O contador indica quando poderá pedir outro código.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);

        try {
            $user->notify(new NotesAccessCodeNotification($code));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'send' => ['Não foi possível enviar o código. Tente novamente.'],
            ]);
        }

        RateLimiter::hit($limiterKey, self::SEND_WINDOW_SECONDS);

        $request->session()->put(self::SESSION_KEY, [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
            'scope' => $scope,
            'user_id' => $user->getAuthIdentifier(),
            'tenant_id' => $this->tenantId(),
        ]);
    }

    private function authenticatedUser(): User
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return $user;
    }

    private function maskEmail(?string $email): string
    {
        if (! is_string($email) || ! str_contains($email, '@')) {
            return 'e-mail cadastrado';
        }

        [$localPart, $domain] = explode('@', $email, 2);

        return Str::substr($localPart, 0, 1).str_repeat('*', max(Str::length($localPart) - 1, 2)).'@'.$domain;
    }

    private function limiterKey(User $user): string
    {
        return 'notes-email-code:'.$this->tenantId().':'.$user->getAuthIdentifier();
    }

    private function tenantId(): string
    {
        return (string) (tenancy()->tenant?->getTenantKey() ?? '');
    }

    private function hasActiveAuthorization(Request $request, User $user, string $scope): bool
    {
        $authorization = $request->session()->get(self::AUTHORIZATION_KEY.'.'.$scope);
        $isAuthorized = is_array($authorization)
            && ($authorization['expires_at'] ?? 0) > now()->timestamp
            && (string) ($authorization['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($authorization['tenant_id'] ?? '') === $this->tenantId();

        if (! $isAuthorized) {
            $request->session()->forget(self::AUTHORIZATION_KEY.'.'.$scope);
        }

        return $isAuthorized;
    }

    private function resolveScope(?string $scope): ?string
    {
        if ($scope === null) {
            return 'discipline';
        }

        return in_array($scope, self::SCOPES, true) ? $scope : null;
    }

    private function redirectToNotesDestination(Request $request): SymfonyResponse
    {
        $destination = $request->session()->pull(self::RETURN_TO_KEY);

        if (is_string($destination) && $this->isInternalDestination($destination)) {
            $path = parse_url($destination, PHP_URL_PATH);

            if (is_string($path) && Str::endsWith($path, ['/exportar-pauta/excel', '/mini-pauta/excel'])) {
                return Inertia::location(url($destination));
            }

            return redirect()->to($destination);
        }

        return redirect()->route('tenant.dashboard');
    }

    private function rememberRequestedDestination(Request $request): void
    {
        if ($request->session()->has(self::RETURN_TO_KEY)) {
            return;
        }

        $destination = $request->query('return_to');

        if (is_string($destination) && $this->isInternalDestination($destination)) {
            $request->session()->put(self::RETURN_TO_KEY, $destination);
        }
    }

    private function isInternalDestination(string $destination): bool
    {
        $parts = parse_url($destination);
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';

        return is_array($parts)
            && ! isset($parts['scheme'])
            && ! isset($parts['host'])
            && Str::startsWith($path, '/')
            && ! Str::startsWith($path, '//')
            && ! Str::contains($path, '\\');
    }
}
