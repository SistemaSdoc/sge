<?php

namespace App\Actions\Central\Auth;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Valida o tenant de origem e inicia o redirect stateful do Socialite. */
class BeginTenantGoogleLogin
{
    /**
     * Guarda o contexto validado na sessão central, depois redirecciona para o provider.
     *
     * @throws HttpExceptionInterface Se a origem não for válida ou o tenant não puder aceder.
     */
    public function handle(Request $request): RedirectResponse
    {
        $tenantOrigin = $this->resolveTenantOrigin((string) $request->query('tenant', ''));
        $tenantDomain = $this->resolveTenantDomain($tenantOrigin);

        $tenant = Tenant::query()
            ->whereHas('domains', fn ($query) => $query->where('domain', $tenantDomain))
            ->first();

        /** Tenant inexistente ou inactivo: responde 404 e não inicia OAuth. */
        abort_unless($tenant?->canAccess(), 404, 'Tenant inválido ou indisponível.');

        $request->session()->put([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => "{$tenantOrigin}/",
        ]);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Aceita uma origem HTTP(S) sem credenciais nem componentes adicionais de URL.
     *
     * @throws HttpExceptionInterface Se a origem tiver uma forma não permitida.
     */
    private function resolveTenantOrigin(string $tenantOrigin): string
    {
        $parsedOrigin = parse_url($tenantOrigin);

        /** Origem inválida ou com caminho/query: responde 400 antes de consultar tenants. */
        if (
            $parsedOrigin === false ||
            ! \in_array(strtolower($parsedOrigin['scheme'] ?? ''), ['http', 'https'], true) ||
            blank($parsedOrigin['host'] ?? null) ||
            isset($parsedOrigin['user']) ||
            isset($parsedOrigin['pass']) ||
            ! \in_array($parsedOrigin['path'] ?? '', ['', '/'], true) ||
            isset($parsedOrigin['query']) ||
            isset($parsedOrigin['fragment'])
        ) {
            abort(400, 'Origem do tenant inválida.');
        }

        $host = strtolower($parsedOrigin['host']);
        $scheme = strtolower($parsedOrigin['scheme']);
        $isLocalDomain = \in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
            || str_ends_with($host, '.localhost');

        /** Produção força HTTPS; localhost conserva esquema e porta para o ambiente de desenvolvimento. */
        if (! $isLocalDomain) {
            return "https://{$host}";
        }

        $port = isset($parsedOrigin['port']) ? ":{$parsedOrigin['port']}" : '';

        return "{$scheme}://{$host}{$port}";
    }

    /** Extrai o host guardado pelo Stancl; a porta fica apenas na origem de retorno. */
    private function resolveTenantDomain(string $tenantOrigin): string
    {
        $parsed = parse_url($tenantOrigin);

        return strtolower($parsed['host'] ?? '');
    }
}
