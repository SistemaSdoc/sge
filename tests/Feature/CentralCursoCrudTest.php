<?php

use App\Models\Central\Curso;
use App\Models\Central\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function centralCursoAdmin(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::create([
        'name' => 'cursos.create',
        'guard_name' => 'web',
    ]));
    $user->assignRole($role);

    return $user;
}

test('superadmin consegue criar e listar um curso no catalogo central', function (): void {
    $user = centralCursoAdmin();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.cursos.store'), [
            'nome' => 'Gestão de Sistemas',
            'descricao' => 'Curso central.',
            'duracao_anos' => 4,
            'status' => 1,
        ])
        ->assertRedirect(route('central.dashboard.cursos.index'));

    expect(Curso::query()->where('nome', 'Gestão de Sistemas')->first())
        ->not->toBeNull();

    $this->actingAs($user, 'web')
        ->get(route('central.dashboard.cursos.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('central/cursos/index')
            ->where('cursos.data.0.nome', 'Gestão de Sistemas'));
});

test('superadmin arquiva e restaura um curso do catalogo central', function (): void {
    $user = centralCursoAdmin();
    $curso = Curso::query()->create([
        'nome' => 'Curso Arquivado',
        'descricao' => 'Curso central.',
        'duracao_anos' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user, 'web')
        ->delete(route('central.dashboard.cursos.destroy', $curso))
        ->assertRedirect(route('central.dashboard.cursos.index'));

    expect(Curso::query()->find($curso->getKey()))->toBeNull()
        ->and(Curso::withTrashed()->find($curso->getKey()))->not->toBeNull();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.cursos.restore', $curso))
        ->assertRedirect(route('central.dashboard.cursos.index'));

    expect(Curso::query()->find($curso->getKey()))->not->toBeNull();
});
