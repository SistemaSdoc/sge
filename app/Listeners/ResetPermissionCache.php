<?php

namespace App\Listeners;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;

class ResetPermissionCache
{
    public function handle(TenancyInitialized|TenancyEnded $event): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
