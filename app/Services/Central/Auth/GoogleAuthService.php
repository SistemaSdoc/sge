<?php

namespace App\Services\Central\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    public function handleRedirect(Request $request): RedirectResponse
    {
        $tenantOrigin = $this->resolveTenantOrigin($request);

        if ($tenantOrigin !== null) {
            $request->session()->put('google_tenant_domain', $tenantOrigin);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleCallback(Request $request): string
    {
        $tenantOrigin = $this->resolveTenantOrigin($request);

        if ($tenantOrigin === null) {
            throw new UnauthorizedGoogleUserException;
        }

        $request->session()->forget('google_tenant_domain');

        $tenantDomain = $this->resolveTenantDomain($tenantOrigin);

        $tenant = Tenant::query()
            ->whereHas('domains', fn ($query) => $query->where('domain', $tenantDomain))
            ->first();

        if ($tenant === null) {
            throw new UnauthorizedGoogleUserException;
        }

        $socialiteUser = Socialite::driver('google')->user();

        $tenantUser = $tenant->run(fn () => User::query()
            ->where('email', $socialiteUser->getEmail())
            ->first());

        if ($tenantUser === null) {
            throw new UnauthorizedGoogleUserException;
        }

        if (
            $tenantUser->google_id !== null &&
            $tenantUser->google_id !== $socialiteUser->getId()
        ) {
            throw new UnauthorizedGoogleUserException;
        }

        if ($tenantUser->google_id === null) {
            $tenantUser->update([
                'google_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);
        }

        $token = ImpersonationToken::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $tenantUser->getKey(),
            'redirect_url' => rtrim($tenantOrigin, '/').'/dashboard',
            'auth_guard' => 'tenant',
        ]);

        return rtrim($tenantOrigin, '/').'/token/'.$token->token;
    }

    private function resolveTenantOrigin(Request $request): ?string
    {
        $tenantOrigin = $request->query('tenant') ?? $request->session()->get('google_tenant_domain');

        if (blank($tenantOrigin)) {
            return null;
        }

        $tenantOrigin = trim((string) $tenantOrigin, " \t\n\r/");

        if (! Str::startsWith(strtolower($tenantOrigin), ['http://', 'https://'])) {
            $tenantOrigin = 'http://'.$tenantOrigin;
        }

        return rtrim($tenantOrigin, '/');
    }

    private function resolveTenantDomain(string $tenantOrigin): string
    {
        $parsed = parse_url($tenantOrigin);

        $host = $parsed['host'] ?? $tenantOrigin;
        $port = $parsed['port'] ?? null;

        return $port !== null ? $host.':'.$port : $host;
    }
}
