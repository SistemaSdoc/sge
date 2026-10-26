<?php

use App\Actions\Tenant\User\UpdateUserPermissions;
use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

it('updates direct permissions without removing inherited permissions', function () {
    Permission::findOrCreate('usuarios.update', 'tenant');
    Permission::findOrCreate('usuarios.viewAny', 'tenant');
    Permission::findOrCreate('usuarios.view', 'tenant');
    Permission::findOrCreate('usuarios.create', 'tenant');
    Permission::findOrCreate('usuarios.delete', 'tenant');
    Permission::findOrCreate('usuarios.gerir', 'tenant');
    Permission::findOrCreate('alunos.viewAny', 'tenant');
    Permission::findOrCreate('notas.viewAny', 'tenant');

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

    $this->actingAs($admin, 'tenant');

    app(UpdateUserPermissions::class)->handle(
        $target,
        ['alunos.viewAny', 'notas.viewAny'],
        $admin,
    );

    $target->refresh();

    expect($target->hasDirectPermission('alunos.viewAny'))->toBeTrue()
        ->and($target->hasDirectPermission('notas.viewAny'))->toBeTrue();
});

it('preserves direct permissions that a subdirector cannot manage', function (): void {
    $directorPermission = Permission::findOrCreate('instituicoes.view', 'tenant');
    $subdirectorPermission = Permission::findOrCreate('instituicoes.update', 'tenant');
    $directorRole = Role::findOrCreate('Director', 'tenant');
    $subdirectorRole = Role::findOrCreate('Subdirector', 'tenant');
    $directorRole->syncPermissions([$directorPermission]);
    $subdirectorRole->syncPermissions([$subdirectorPermission]);

    $subdirector = User::factory()->create([
        'nome' => 'Subdirector permissions',
        'email' => 'subdirector.permissions@test.local',
    ]);
    $subdirector->assignRole($subdirectorRole);

    $target = User::factory()->create([
        'nome' => 'Utilizador sem role',
        'email' => 'target.no-role@test.local',
    ]);
    $target->givePermissionTo($directorPermission);

    app(UpdateUserPermissions::class)->handle(
        $target,
        ['instituicoes.update'],
        $subdirector,
    );

    $target->refresh();

    expect($target->hasDirectPermission('instituicoes.view'))->toBeTrue()
        ->and($target->hasDirectPermission('instituicoes.update'))->toBeTrue();
});

it('returns permission groups as indexed lists for a subdirector', function (): void {
    $viewPermission = Permission::findOrCreate('instituicoes.view', 'tenant');
    $updatePermission = Permission::findOrCreate('instituicoes.update', 'tenant');
    $directorRole = Role::findOrCreate('Director', 'tenant');
    $subdirectorRole = Role::findOrCreate('Subdirector', 'tenant');

    $directorRole->syncPermissions([$viewPermission]);
    $subdirectorRole->syncPermissions([$updatePermission]);

    $subdirector = User::factory()->create([
        'nome' => 'Subdirector teste',
        'email' => 'subdirector.permissions@test.local',
    ]);
    $subdirector->assignRole($subdirectorRole);

    $groups = app(RoleManagementService::class)->groupedPermissions($subdirector);
    $institutionsGroup = collect($groups)->firstWhere('label', 'Instituições');

    expect($institutionsGroup)->not->toBeNull()
        ->and(array_is_list($institutionsGroup['permissions']))->toBeTrue()
        ->and($institutionsGroup['permissions'])->toHaveCount(1)
        ->and($institutionsGroup['permissions'][0]['value'])->toBe('instituicoes.update');
});
