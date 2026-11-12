<?php

namespace App\Notifications\Concerns;

use App\Services\Tenant\TenantUrlGenerator;

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
        return app(TenantUrlGenerator::class)->route($routeName, $parameters, $domain);
    }
}
