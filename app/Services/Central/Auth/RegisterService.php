<?php

namespace App\Services\Central\Auth;

use App\Services\Central\TenantService;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function __construct(private TenantService $tenantService)
    {
    }

    /**
     * Register a new institution with its owner.
     *
     * @throws ValidationException
     */
    public function register(array $data): \App\Models\Central\Tenant
    {
        $tenant = $this->tenantService->createTenant($data);

        $this->tenantService->savePendingTenantData($tenant, $data);

        return $tenant;
    }
}