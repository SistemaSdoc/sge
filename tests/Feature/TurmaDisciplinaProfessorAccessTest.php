<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Inscricao;
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

test('professor sees assigned disciplines and can search turma lists', function () {
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
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma A',
        'max_alunos' => 30,
    ]);

    $disciplinaAutorizada = Disciplina::create(['nome' => 'Matemática', 'sigla' => 'MAT']);
    $disciplinaNaoAutorizada = Disciplina::create(['nome' => 'Português', 'sigla' => 'POR']);

    $classeTurnoDisciplinaAutorizada = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplinaAutorizada->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);
    $classeTurnoDisciplinaNaoAutorizada = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplinaNaoAutorizada->id,
        'ano_lectivo_id' => $anoLectivo->id,
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

    $candidato = Candidato::create([
        'nome' => 'Aluna Encontrada',
        'bi' => '020619207LA055',
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'instituicao_id' => $instituicao->id,
        'matricula' => 'MAT-TESTE-123',
    ]);
    $aluno->turmas()->attach($turma->id, [
        'activo' => true,
        'situacao' => 'activo',
    ]);

    GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo Energia',
        'tema_grupo' => 'Energias renováveis',
        'status' => 'pendente',
    ]);

    $response = $this->actingAs($user)->get(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
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

    $searchResponse = $this->actingAs($user)->get(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'search_alunos' => 'Aluna Encontrada',
        'search_disciplinas' => 'MAT',
        'search_grupos' => 'renováveis',
    ]));

    $searchResponse->assertOk();
    $searchResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->has('alunos.data', 1)
        ->where('alunos.data.0.nome', 'Aluna Encontrada')
        ->has('disciplinas.data', 1)
        ->where('disciplinas.data.0.nome', $disciplinaAutorizada->nome)
        ->has('grupos.data', 1)
        ->where('totalAlunos', 1)
        ->where('totalDisciplinas', 1)
    );

    $matriculaResponse = $this->actingAs($user)->get(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'search_alunos' => 'MAT-TESTE-123',
    ]));

    $matriculaResponse->assertOk();
    $matriculaResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->has('alunos.data', 1)
        ->where('alunos.data.0.matricula', 'MAT-TESTE-123')
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

test('turma removal returns an error when discipline professor assignments exist', function () {
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
    $professor = Professor::create(['user_id' => $user->id, 'especialidade' => 'Matemática']);

    Permission::create(['name' => 'turmas.update']);
    Permission::create(['name' => 'turmas.delete']);
    $user->givePermissionTo(['turmas.update', 'turmas.delete']);

    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
        'turma_id' => $turma->id,
        'professor_id' => $professor->id,
    ]);

    $response = $this->actingAs($user, 'tenant')->delete(
        "/dashboard/instituicoes/{$instituicao->id}/cursos-tutelados/{$cursoTutelado->id}/classes/{$cursoClasse->id}/turnos/{$cursoClasseTurno->id}/turmas/{$turma->id}"
    );

    $response->assertSessionHasErrors([
        'turma' => 'Não é possível remover uma turma que tem disciplinas ou professores associados.',
    ]);
    $this->assertDatabaseHas('turmas', ['id' => $turma->id]);
});

test('turma removal clears unassigned discipline rows without academic history', function () {
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
        'tem_professor' => false,
    ]);
    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    Permission::create(['name' => 'turmas.update']);
    Permission::create(['name' => 'turmas.delete']);
    $user->givePermissionTo(['turmas.update', 'turmas.delete']);

    $atribuicao = TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
        'turma_id' => $turma->id,
        'professor_id' => null,
    ]);

    $response = $this->actingAs($user, 'tenant')->delete(
        "/dashboard/instituicoes/{$instituicao->id}/cursos-tutelados/{$cursoTutelado->id}/classes/{$cursoClasse->id}/turnos/{$cursoClasseTurno->id}/turmas/{$turma->id}"
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('turmas', ['id' => $turma->id]);
    $this->assertDatabaseMissing('turma_disciplina_professor', ['id' => $atribuicao->id]);
});
