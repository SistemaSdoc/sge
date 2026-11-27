<?php

namespace App\Notifications\Concerns;

trait TenantNotificationUrl
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function tenantNotificationUrl(
        string $routeName,
        array $parameters = [],
        ?string $domain = null
    ): string {
        $domain ??= tenant()?->domains()->first()?->domain;

        return $domain
            ? tenant_route($domain, $routeName, $parameters)
            : route($routeName, $parameters);
    }
}
