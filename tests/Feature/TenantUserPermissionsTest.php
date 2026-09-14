<?php

use App\Models\Tenant\User;
use Spatie\Permission\Models\Permission;

it('loads the user permissions page and updates direct permissions', function () {
    Permission::findOrCreate('usuarios.update', 'tenant');
    Permission::findOrCreate('usuarios.viewAny', 'tenant');
    Permission::findOrCreate('usuarios.view', 'tenant');
    Permission::findOrCreate('usuarios.create', 'tenant');
    Permission::findOrCreate('usuarios.delete', 'tenant');
    Permission::findOrCreate('usuarios.gerir', 'tenant');

    $admin = User::factory()->create([
        'nome' => 'Admin Permissões',
        'email' => 'admin.permissoes@test.local',
        'instituicao_id' => 1,
    ]);

    $admin->givePermissionTo([
        'usuarios.update',
        'usuarios.viewAny',
        'usuarios.view',
        'usuarios.create',
        'usuarios.delete',
        'usuarios.gerir',
    ]);

    $target = User::factory()->create([
        'nome' => 'Usuário alvo',
        'email' => 'user.permissoes@test.local',
        'instituicao_id' => 1,
    ]);

    $target->givePermissionTo('alunos.viewAny');

    $this->actingAs($admin, 'tenant')
        ->get(route('tenant.dashboard.users.permissions', ['user' => $target->id]))
        ->assertOk();

    $this->actingAs($admin, 'tenant')
        ->put(route('tenant.dashboard.users.permissions.update', ['user' => $target->id]), [
            'permissions' => ['alunos.viewAny', 'notas.viewAny'],
        ])
        ->assertRedirect(route('tenant.dashboard.users.index'));

    $target->refresh();

    expect($target->hasDirectPermission('alunos.viewAny'))->toBeTrue()
        ->and($target->hasDirectPermission('notas.viewAny'))->toBeTrue();
});
