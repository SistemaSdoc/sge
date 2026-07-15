<?php

use App\Models\ItemPagavel;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('grants item pagavel access through the configured gates', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Secretaria']);

    $role->givePermissionTo([
        Permission::create(['name' => 'itemspagaveis.viewAny']),
        Permission::create(['name' => 'itemspagaveis.create']),
    ]);
    $user->assignRole($role);

    expect(Gate::forUser($user)->allows('itemspagaveis.viewAny'))->toBeTrue();
    expect(Gate::forUser($user)->allows('itemspagaveis.create'))->toBeTrue();
    expect(Gate::forUser($user)->allows('viewAny', ItemPagavel::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('create', ItemPagavel::class))->toBeTrue();
});

it('grants pagamento access through the configured gates', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Secretaria']);

    $role->givePermissionTo([
        Permission::create(['name' => 'pagamentos.view']),
        Permission::create(['name' => 'pagamentos.gerir']),
    ]);
    $user->assignRole($role);

    expect(Gate::forUser($user)->allows('pagamentos.view'))->toBeTrue();
    expect(Gate::forUser($user)->allows('pagamentos.gerir'))->toBeTrue();
    expect(Gate::forUser($user)->allows('viewAny', Pagamento::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('create', Pagamento::class))->toBeTrue();
});
