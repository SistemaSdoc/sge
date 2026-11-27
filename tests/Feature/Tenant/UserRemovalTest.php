<?php

use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

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

it('blocks user removal and reports the teacher discipline assignment', function (): void {
    $institutionId = (string) Str::uuid();
    $director = User::factory()->create(['instituicao_id' => $institutionId]);
    $director->assignRole(Role::findOrCreate('Director', 'tenant'));
    $director->givePermissionTo([
        Permission::findOrCreate('usuarios.delete', 'tenant'),
        Permission::findOrCreate('usuarios.gerir', 'tenant'),
    ]);

    $target = User::factory()->create(['instituicao_id' => $institutionId]);
    $target->assignRole(Role::findOrCreate('Professor', 'tenant'));
    $professor = Professor::create(['user_id' => $target->id]);
    $assignmentId = (string) Str::uuid();

    DB::statement('PRAGMA foreign_keys = OFF');

    try {
        DB::table('turma_disciplina_professor')->insert([
            'id' => $assignmentId,
            'classe_turno_disciplina_id' => (string) Str::uuid(),
            'turma_id' => (string) Str::uuid(),
            'professor_id' => $professor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } finally {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    $response = $this->actingAs($director, 'tenant')
        ->from('/dashboard/users')
        ->delete("/dashboard/users/{$target->id}");

    $response->assertRedirect()
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['type'] === 'error'
            && str_contains($flash['toast']['message'], 'disciplina/turma'));

    $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('professores', ['id' => $professor->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('turma_disciplina_professor', ['id' => $assignmentId]);
    expect($target->fresh()->hasRole('Professor'))->toBeTrue();
});

it('blocks professor removal and reports the course assignment', function (): void {
    $institutionId = (string) Str::uuid();
    $director = User::factory()->create(['instituicao_id' => $institutionId]);
    $director->givePermissionTo(Permission::findOrCreate('professores.delete', 'tenant'));

    $target = User::factory()->create(['instituicao_id' => $institutionId]);
    $professor = Professor::create(['user_id' => $target->id]);
    $courseId = (string) Str::uuid();
    $assignmentId = (string) Str::uuid();

    DB::statement('PRAGMA foreign_keys = OFF');

    try {
        DB::table('curso_tutelado')->insert([
            'id' => $courseId,
            'instituicao_curso_id' => (string) Str::uuid(),
            'instituicao_tutora_id' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('curso_tutelado_professor')->insert([
            'id' => $assignmentId,
            'curso_tutelado_id' => $courseId,
            'professor_id' => $professor->id,
            'tipo' => 'colaborador',
            'coordenador' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } finally {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    $response = $this->actingAs($director, 'tenant')
        ->from('/dashboard/professores')
        ->delete("/dashboard/professores/{$professor->id}");

    $response->assertRedirect()
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['type'] === 'error'
            && str_contains($flash['toast']['message'], 'curso(s)'));

    $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('professores', ['id' => $professor->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('curso_tutelado_professor', ['id' => $assignmentId]);
});

it('removes an unassigned professor together with the user profile', function (): void {
    $institutionId = (string) Str::uuid();
    $director = User::factory()->create(['instituicao_id' => $institutionId]);
    $director->assignRole(Role::findOrCreate('Director', 'tenant'));
    $director->givePermissionTo([
        Permission::findOrCreate('usuarios.delete', 'tenant'),
        Permission::findOrCreate('usuarios.gerir', 'tenant'),
    ]);

    $target = User::factory()->create(['instituicao_id' => $institutionId]);
    $target->assignRole(Role::findOrCreate('Professor', 'tenant'));
    $professor = Professor::create(['user_id' => $target->id]);

    $response = $this->actingAs($director, 'tenant')
        ->delete("/dashboard/users/{$target->id}");

    $response->assertRedirect(route('tenant.dashboard.users.index'))
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['type'] === 'success');

    $this->assertSoftDeleted('users', ['id' => $target->id]);
    $this->assertSoftDeleted('professores', ['id' => $professor->id]);
});
