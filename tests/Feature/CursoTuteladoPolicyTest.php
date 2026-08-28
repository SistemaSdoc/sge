<?php

use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('permits a professor to view a curso tutelado when attached to it and in the same institution', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 4,
    ]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicao->id,
        'duracao_anos' => 4,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);

    $professor = Professor::create([
        'user_id' => $user->id,
        'especialidade' => 'Matemática',
    ]);

    $permission = Permission::create(['name' => 'curso-tutelado.view']);
    $role = Role::create(['name' => 'Professor']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    $cursoTutelado->professores()->attach($professor->id, [
        'tipo' => 'principal',
        'coordenador' => true,
    ]);

    expect(Gate::forUser($user)->allows('view', $cursoTutelado))->toBeTrue();
});

it('permits a colegio user to view a curso tutelado that is tutelado by an instituto', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $instituto = Instituicao::create([
        'nome' => 'Instituto Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 4,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $colegio->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituto->id,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $colegio->id,
    ]);

    $permission = Permission::create(['name' => 'curso-tutelado.view']);
    $role = Role::create(['name' => 'Secretaria']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    expect(Gate::forUser($user)->allows('view', $cursoTutelado))->toBeTrue();
});

it('allows a colegio user to access the alunos endpoint for a tutelado course', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $instituto = Instituicao::create([
        'nome' => 'Instituto Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 4,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $colegio->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituto->id,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $colegio->id,
    ]);

    $permission = Permission::create(['name' => 'curso-tutelado.view']);
    $role = Role::create(['name' => 'Secretaria']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    $response = $this->actingAs($user)->get("/dashboard/instituicoes/{$colegio->id}/cursos-tutelados/{$cursoTutelado->id}/alunos");

    $response->assertStatus(200);
});
