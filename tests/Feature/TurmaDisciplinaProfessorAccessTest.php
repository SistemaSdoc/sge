<?php

use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('professor sees only the disciplines he teaches in the turma show response', function () {
    $instituicao = Instituicao::create(['nome' => 'Instituição Teste']);
    $curso = Curso::create(['nome' => 'Curso Teste', 'duracao_anos' => 3, 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
        'nome' => 'Curso Tutelado Teste',
    ]);
    $classe = Classe::create(['nome' => '12ª']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'nome' => 'Turma A',
        'max_alunos' => 30,
    ]);

    $disciplinaAutorizada = Disciplina::create(['nome' => 'Matemática', 'sigla' => 'MAT']);
    $disciplinaNaoAutorizada = Disciplina::create(['nome' => 'Português', 'sigla' => 'POR']);

    $classeTurnoDisciplinaAutorizada = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplinaAutorizada->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);
    $classeTurnoDisciplinaNaoAutorizada = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplinaNaoAutorizada->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);
    $professor = Professor::create(['user_id' => $user->id, 'especialidade' => 'Matemática']);

    Role::create(['name' => 'Professor']);
    Permission::create(['name' => 'turmas.view']);
    Permission::create(['name' => 'turmas.viewAny']);
    Permission::create(['name' => 'acessos.viewAny']);
    $user->assignRole('Professor');
    $user->givePermissionTo(['turmas.view', 'turmas.viewAny', 'acessos.viewAny']);

    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplinaAutorizada->id,
        'turma_id' => $turma->id,
        'professor_id' => $professor->id,
    ]);

    $response = $this->actingAs($user)->get(route('turmas.show', [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('turma.data.id', $turma->id)
        ->has('disciplinas.data', 1)
        ->where('disciplinas.data.0.nome', $disciplinaAutorizada->nome)
    );
});

test('professor cannot access notes for a discipline he does not teach', function () {
    $instituicao = Instituicao::create(['nome' => 'Instituição Teste']);
    $curso = Curso::create(['nome' => 'Curso Teste', 'duracao_anos' => 3, 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
        'nome' => 'Curso Tutelado Teste',
    ]);
    $classe = Classe::create(['nome' => '12ª']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'nome' => 'Turma A',
        'max_alunos' => 30,
    ]);

    $disciplina = Disciplina::create(['nome' => 'Matemática', 'sigla' => 'MAT']);
    $classeTurnoDisciplina = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplina->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $anotherUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professorOutro = Professor::create(['user_id' => $anotherUser->id, 'especialidade' => 'Física']);
    $professor = Professor::create(['user_id' => $user->id, 'especialidade' => 'Matemática']);

    Role::create(['name' => 'Professor']);
    Permission::create(['name' => 'turmas.view']);
    Permission::create(['name' => 'turmas.viewAny']);
    Permission::create(['name' => 'acessos.viewAny']);
    $user->assignRole('Professor');
    $user->givePermissionTo(['turmas.view', 'turmas.viewAny', 'acessos.viewAny']);

    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
        'turma_id' => $turma->id,
        'professor_id' => $professorOutro->id,
    ]);

    $response = $this->actingAs($user)->get("/dashboard/instituicoes/{$instituicao->id}/cursos-tutelados/{$cursoTutelado->id}/classes/{$cursoClasse->id}/turnos/{$cursoClasseTurno->id}/turmas/{$turma->id}/disciplinas/{$classeTurnoDisciplina->id}/notas");

    $response->assertForbidden();
});
