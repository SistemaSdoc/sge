<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\PasswordUpdateRequest;
use App\Http\Requests\Tenant\Settings\TwoFactorAuthenticationRequest;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $props = [
            'hasPassword' => ! is_null($user->password),
            'facebookConnected' => filled($user->facebook_id),
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys()
                ? $user
                    ->passkeys()
                    ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                    ->latest()
                    ->get()
                    ->map(fn ($passkey) => [
                        'id' => $passkey->id,
                        'name' => $passkey->name,
                        'authenticator' => $passkey->authenticator,
                        'created_at_diff' => $passkey->created_at->diffForHumans(),
                        'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
                    ])
                    ->values()
                    ->all()
                : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $user->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('tenant/settings/security', $props);
    }

    /**
     * Inicia a associação do Facebook ao utilizador autenticado no tenant.
     */
    public function beginFacebookLink(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $returnTo = $this->normalizeReturnUrl((string) $request->query('return_to', ''));

        $tenantId = tenant()?->getKey();

        abort_unless($tenantId !== null, 403, 'Sem tenant activo para associar a conta do Facebook.');

        $payload = [
            'tenant_id' => $tenantId,
            'user_id' => $user->getKey(),
            'exp' => now()->addMinutes(10)->timestamp,
        ];

        $state = Crypt::encryptString((string) json_encode($payload));
        $tenantOrigin = rtrim((string) $request->getSchemeAndHttpHost(), '/');
        $centralUrl = rtrim((string) config('app.url'), '/');

        if ($centralUrl === '') {
            $centralUrl = 'http://localhost:8001';
        }

        return redirect()->away(
            "{$centralUrl}/auth/facebook/redirect?tenant=".urlencode($tenantOrigin)
            .'&link_state='.urlencode($state)
            .'&return_to='.urlencode($returnTo)
        );
    }

    /**
     * Inicia a associação Google ao utilizador autenticado no tenant.
     */
    public function beginGoogleLink(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $tenantId = tenant()?->getKey();

        abort_unless($tenantId !== null, 403, 'Sem tenant activo para associar a conta Google.');

        $payload = [
            'tenant_id' => $tenantId,
            'user_id' => $user->getKey(),
            'exp' => now()->addMinutes(10)->timestamp,
        ];

        $state = Crypt::encryptString((string) json_encode($payload));
        $tenantOrigin = rtrim((string) $request->getSchemeAndHttpHost(), '/');
        $centralUrl = rtrim((string) config('app.url'), '/');

        if ($centralUrl === '') {
            $centralUrl = 'http://localhost:8001';
        }

        return redirect()->away(
            "{$centralUrl}/auth/google/redirect?tenant=".urlencode($tenantOrigin)
            .'&link_state='.urlencode($state)
            .'&return_to='.urlencode($this->normalizeReturnUrl((string) $request->query('return_to', '')))
        );
    }

    public function disconnectSocialAccount(string $provider): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        [$providerColumn, $otherProviderColumn, $providerName] = match ($provider) {
            'google' => ['google_id', 'facebook_id', 'Google'],
            'facebook' => ['facebook_id', 'google_id', 'Facebook'],
            default => abort(404),
        };

        $hasAnotherSignInMethod = filled($user->password)
            || filled($user->{$otherProviderColumn})
            || (Features::canManagePasskeys() && $user->passkeys()->exists());

        if (! $hasAnotherSignInMethod) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Antes de desconectar, defina uma senha, associe outra conta ou adicione uma passkey.',
            ]);

            return back();
        }

        $user->update([$providerColumn => null]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "A conta {$providerName} foi desconectada.",
        ]);

        return back();
    }

    /**
     * Normaliza a rota para regressar depois da associação do Facebook.
     */
    private function normalizeReturnUrl(string $returnTo): string
    {
        $returnTo = trim($returnTo);

        if ($returnTo === '' || ! str_starts_with($returnTo, '/')) {
            return '/dashboard/settings/security';
        }

        return $returnTo;
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $user->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
