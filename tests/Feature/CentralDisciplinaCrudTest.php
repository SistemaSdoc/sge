<?php

use App\Models\Central\Disciplina;
use App\Models\Central\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function centralDisciplinaAdmin(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::create([
        'name' => 'disciplinas.create',
        'guard_name' => 'web',
    ]));
    $user->assignRole($role);

    return $user;
}

test('superadmin consegue criar e listar uma disciplina no catalogo central', function (): void {
    $user = centralDisciplinaAdmin();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.disciplinas.store'), [
            'nome' => 'Matemática',
            'sigla' => 'MAT',
            'componente' => 'cientifica',
            'carga_horaria' => 60,
            'status' => 1,
        ])
        ->assertRedirect(route('central.dashboard.disciplinas.index'));

    expect(Disciplina::query()->where('sigla', 'MAT')->first())
        ->not->toBeNull();

    $this->actingAs($user, 'web')
        ->get(route('central.dashboard.disciplinas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('central/disciplinas/index')
            ->where('disciplinas.data.0.sigla', 'MAT'));
});

test('superadmin arquiva e restaura uma disciplina do catalogo central', function (): void {
    $user = centralDisciplinaAdmin();
    $disciplina = Disciplina::query()->create([
        'nome' => 'Disciplina Arquivada',
        'sigla' => 'ARQ',
        'carga_horaria' => 60,
        'status' => 1,
    ]);

    $this->actingAs($user, 'web')
        ->delete(route('central.dashboard.disciplinas.destroy', $disciplina))
        ->assertRedirect(route('central.dashboard.disciplinas.index'));

    expect(Disciplina::query()->find($disciplina->getKey()))->toBeNull()
        ->and(Disciplina::withTrashed()->find($disciplina->getKey()))->not->toBeNull();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.disciplinas.restore', $disciplina))
        ->assertRedirect(route('central.dashboard.disciplinas.index'));

    expect(Disciplina::query()->find($disciplina->getKey()))->not->toBeNull();
});
