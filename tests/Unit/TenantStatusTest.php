<?php

use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenantJob;
use App\Models\Central\Tenant;
use App\Services\Central\TenantService;

test('only active and trial tenants can access the application', function () {
    expect(TenantStatus::ACTIVE->canAccess())->toBeTrue()
        ->and(TenantStatus::TRIAL->canAccess())->toBeTrue()
        ->and(TenantStatus::PENDING->canAccess())->toBeFalse()
        ->and(TenantStatus::PROVISIONING->canAccess())->toBeFalse()
        ->and(TenantStatus::SUSPENDED->canAccess())->toBeFalse()
        ->and(TenantStatus::ARCHIVED->canAccess())->toBeFalse();
});

test('provisioning and failed tenants expose only safe transitions', function () {
    $service = new TenantService;

    expect($service->getAvailableStatusTransitions(new Tenant(['status' => TenantStatus::PROVISIONING])))
        ->toBe([])
        ->and($service->getAvailableStatusTransitions(new Tenant(['status' => TenantStatus::FAILED])))
        ->toBe([
            'active' => 'Tentar activar novamente',
            'trial' => 'Tentar activar em período de teste',
        ]);
});

test('tenant provisioning jobs are unique per tenant', function () {
    $tenant = new Tenant;
    $tenant->setKeyType('string');
    $tenant->setRawAttributes(['id' => 'tenant-test']);

    expect((new ProvisionTenantJob($tenant))->uniqueId())->toBe('tenant-test');
});
