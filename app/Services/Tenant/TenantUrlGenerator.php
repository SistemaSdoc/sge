<?php

namespace App\Services\Tenant;

use LogicException;

class TenantUrlGenerator
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function route(string $routeName, array $parameters = [], ?string $domain = null): string
    {
        $tenant = tenant();
        $domain ??= $tenant?->relationLoaded('domains')
            ? $tenant->getRelation('domains')->first()?->domain
            : $tenant?->domains()->value('domain');

        if (! $domain) {
            throw new LogicException("Cannot generate tenant URL for route [{$routeName}] without a tenant domain.");
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);

        if (! is_string($scheme) || $scheme === '') {
            throw new LogicException('Cannot generate tenant URL without a configured application URL scheme.');
        }

        $url = tenant_route($domain, $routeName, $parameters);

        return preg_replace('/^[a-z][a-z0-9+.-]*:/i', "{$scheme}:", $url, 1)
            ?? throw new LogicException("Could not normalize the URL scheme for route [{$routeName}].");
    }
}
