<?php

use App\Models\Curso;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Professor;
use App\Models\User;
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
