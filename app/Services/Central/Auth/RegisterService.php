<?php

namespace App\Services\Central\Auth;

use App\Models\Central\Tenant;
use App\Services\Central\TenantService;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function __construct(private TenantService $tenantService) {}

    /**
     * Register a new institution with its owner.
     *
     * @throws ValidationException
     */
    public function register(array $data): Tenant
    {
        return $this->tenantService->createTenant($data);
    }
}
