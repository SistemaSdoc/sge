<?php

namespace App\Actions\Central\Auth;

use App\Models\Central\Tenant;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResolveTenantOAuthContext
{
    /**
     * @return array{tenant: Tenant, origin: string}
     *
     * @throws HttpExceptionInterface If the tenant origin is invalid or unavailable.
     */
    public function handle(string $origin): array
    {
        $origin = $this->normalizeOrigin($origin);
        $domain = strtolower((string) parse_url($origin, PHP_URL_HOST));

        $tenant = Tenant::query()
            ->whereHas('domains', fn ($query) => $query->where('domain', $domain))
            ->first();

        abort_unless($tenant?->canAccess(), 404, 'Tenant inválido ou indisponível.');

        return [
            'tenant' => $tenant,
            'origin' => $origin,
        ];
    }

    /**
     * Aceita uma origem HTTP(S) sem credenciais nem componentes adicionais de URL.
     *
     * @throws HttpExceptionInterface If the origin has a disallowed form.
     */
    private function normalizeOrigin(string $origin): string
    {
        $parsedOrigin = parse_url($origin);

        if (
            $parsedOrigin === false ||
            ! \in_array(strtolower((string) ($parsedOrigin['scheme'] ?? '')), ['http', 'https'], true) ||
            blank($parsedOrigin['host'] ?? null) ||
            isset($parsedOrigin['user']) ||
            isset($parsedOrigin['pass']) ||
            ! \in_array($parsedOrigin['path'] ?? '', ['', '/'], true) ||
            isset($parsedOrigin['query']) ||
            isset($parsedOrigin['fragment'])
        ) {
            abort(400, 'Origem do tenant inválida.');
        }

        $host = strtolower((string) $parsedOrigin['host']);
        $scheme = strtolower((string) $parsedOrigin['scheme']);
        $isLocalDomain = \in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
            || Str::endsWith($host, '.localhost');

        if (! $isLocalDomain) {
            return "https://{$host}";
        }

        $port = isset($parsedOrigin['port']) ? ":{$parsedOrigin['port']}" : '';

        return "{$scheme}://{$host}{$port}";
    }
}
