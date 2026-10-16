<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('coordenador vê todas as turmas dos cursos que coordena', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso Teste', 'duracao_anos' => 3, 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $classe = Classe::create(['nome' => '10ª Classe']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $turmaA = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma A',
    ]);
    $turmaB = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma B',
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professor = Professor::create(['user_id' => $user->id, 'especialidade' => 'Matemática']);
    $role = Role::create(['name' => 'Coordenador']);
    $permission = Permission::create(['name' => 'coordenador.manage-turmas']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);
    $cursoTutelado->professores()->attach($professor->id, [
        'tipo' => 'principal',
        'coordenador' => true,
    ]);

    $response = $this->actingAs($user, 'tenant')->get(route('turmas.index'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('turmas.data.0.id', $turmaA->id)
        ->where('turmas.data.1.id', $turmaB->id)
    );
});
