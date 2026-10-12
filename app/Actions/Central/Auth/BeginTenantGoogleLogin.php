<?php

namespace App\Actions\Central\Auth;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

/** Valida o tenant de origem e inicia o redirect stateful do Socialite. */
class BeginTenantGoogleLogin
{
    public function __construct(private readonly ResolveTenantOAuthContext $tenantOAuthContext) {}

    /**
     * Guarda o contexto validado na sessão central, depois redirecciona para o provider.
     */
    public function handle(Request $request): RedirectResponse
    {
        ['tenant' => $tenant, 'origin' => $tenantOrigin] = $this->tenantOAuthContext->handle(
            (string) $request->query('tenant', ''),
        );

        $request->session()->put([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => "{$tenantOrigin}/",
        ]);

        if ($request->filled('link_state')) {
            $request->session()->put([
                'google_link_state' => (string) $request->query('link_state'),
                'google_link_return_url' => $this->normalizeLinkReturnUrl((string) $request->query('return_to', '')),
            ]);
        } else {
            $request->session()->forget(['google_link_state', 'google_link_return_url']);
        }

        return Socialite::driver('google')->redirect();
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
