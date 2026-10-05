<?php

use App\Models\Central\Tenant;
use App\Models\Central\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('superadmin sees the tenants list after deleting a tenant', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::create([
        'name' => 'tenants.create',
        'guard_name' => 'web',
    ]));
    $user->assignRole($role);

    $tenant = Tenant::create([
        'id' => 'tenant-to-delete',
        'status' => 'pending',
    ]);

    $databaseManager = $tenant->database()->manager();
    $databaseManagerClass = $databaseManager::class;
    $databaseManager = Mockery::mock($databaseManager)->makePartial();
    $databaseManager->shouldReceive('deleteDatabase')
        ->once()
        ->with(Mockery::on(
            fn (Tenant $deletedTenant): bool => $deletedTenant->getKey() === 'tenant-to-delete',
        ))
        ->andReturnTrue();
    $this->app->instance($databaseManagerClass, $databaseManager);

    $this->withHeaders([
        'X-Inertia' => 'true',
    ])->actingAs($user, 'web')
        ->delete(route('central.dashboard.tenants.destroy', $tenant))
        ->assertStatus(303)
        ->assertRedirect(route('central.dashboard.tenants.index'));

    expect(Tenant::query()->find('tenant-to-delete'))->toBeNull();

    $this->flushHeaders()
        ->actingAs($user, 'web')
        ->get(route('central.dashboard.tenants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('central/tenants/index')
            ->has('tenants.data', 0));
});
