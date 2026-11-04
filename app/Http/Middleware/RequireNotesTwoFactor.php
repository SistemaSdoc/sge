<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequireNotesTwoFactor
{
    private const AUTHORIZATION_LIFETIME_MINUTES = 10;

    private const SCOPES = ['discipline', 'history', 'pauta'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $scope = 'discipline'): Response
    {
        abort_unless(in_array($scope, self::SCOPES, true), 404);

        $user = Auth::guard('tenant')->user();
        $authorizationKey = 'notes_access.authorizations.'.$scope;
        $authorization = $request->session()->get($authorizationKey);
        $tenantId = tenancy()->tenant?->getTenantKey();
        $isAuthorized = $user !== null
            && is_array($authorization)
            && ($authorization['expires_at'] ?? 0) > now()->timestamp
            && (string) ($authorization['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($authorization['tenant_id'] ?? '') === (string) ($tenantId ?? '');

        if ($isAuthorized) {
            if ($request->isMethod('GET')) {
                $authorization['active_path'] = $request->getPathInfo();
            }

            $request->session()->put($authorizationKey, $authorization);

            return $next($request);
        }

        $canContinueOnActivePage = $user !== null
            && is_array($authorization)
            && (string) ($authorization['user_id'] ?? '') === (string) $user->getAuthIdentifier()
            && (string) ($authorization['tenant_id'] ?? '') === (string) ($tenantId ?? '')
            && $this->isActivePageRequest($request, $authorization);

        if ($canContinueOnActivePage) {
            $authorization['expires_at'] = now()->addMinutes(self::AUTHORIZATION_LIFETIME_MINUTES)->timestamp;
            $request->session()->put($authorizationKey, $authorization);

            return $next($request);
        }

        if (! $isAuthorized) {
            $request->session()->forget($authorizationKey);
            $this->rememberDestination($request);

            return redirect()->route('tenant.dashboard.notes.two-factor.challenge', ['scope' => $scope]);
        }

        return $next($request);
    }

    private function isActivePageRequest(Request $request, array $authorization): bool
    {
        $activePath = $authorization['active_path'] ?? null;
        $referer = $request->headers->get('referer');
        $refererPath = is_string($referer) ? parse_url($referer, PHP_URL_PATH) : null;

        return is_string($activePath)
            && $activePath === $refererPath
            && (! $request->isMethod('GET') || $activePath === $request->getPathInfo());
    }

    private function rememberDestination(Request $request): void
    {
        if ($request->isMethod('GET')) {
            $request->session()->put('notes_access.return_to', $request->getRequestUri());

            return;
        }

        $referer = $request->headers->get('referer');
        $refererHost = is_string($referer) ? parse_url($referer, PHP_URL_HOST) : null;

        if ($refererHost !== null && $refererHost !== $request->getHost()) {
            return;
        }

        $path = is_string($referer) ? parse_url($referer, PHP_URL_PATH) : null;
        $query = is_string($referer) ? parse_url($referer, PHP_URL_QUERY) : null;
        $destination = $path.($query ? '?'.$query : '');

        if (Str::startsWith($destination, '/') && ! Str::startsWith($destination, '//')) {
            $request->session()->put('notes_access.return_to', $destination);
        }
    }
}
