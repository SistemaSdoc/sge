<?php

namespace App\Notifications\Concerns;

use LogicException;

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

        if (! $domain) {
            throw new LogicException('Cannot generate a tenant notification URL without a tenant domain.');
        }

        return tenant_route($domain, $routeName, $parameters);
    }
}
