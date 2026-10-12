<?php

namespace App\Actions\Central\Auth;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BeginTenantFacebookLogin
{
    public function __construct(private readonly ResolveTenantOAuthContext $tenantOAuthContext) {}

    public function handle(Request $request): RedirectResponse
    {
        ['tenant' => $tenant, 'origin' => $tenantOrigin] = $this->tenantOAuthContext->handle(
            (string) $request->query('tenant', ''),
        );

        $request->session()->put([
            'facebook_tenant_id' => $tenant->getKey(),
            'facebook_tenant_login_url' => "{$tenantOrigin}/",
        ]);

        if ($request->filled('link_state')) {
            $request->session()->put([
                'facebook_link_state' => (string) $request->query('link_state'),
                'facebook_link_return_url' => $this->normalizeLinkReturnUrl((string) $request->query('return_to', '')),
            ]);
        } else {
            $request->session()->forget(['facebook_link_state', 'facebook_link_return_url']);
        }

        return Socialite::driver('facebook')->redirect();
    }

    private function normalizeLinkReturnUrl(string $returnUrl): string
    {
        $returnUrl = trim($returnUrl);

        if ($returnUrl === '' || ! str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            return '/dashboard/settings/security';
        }

        return $returnUrl;
    }
}
