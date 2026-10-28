<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
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
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

test('director sees institutional teachers and the selected professor full weekly schedule', function (): void {
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
    $turnoManha = Turno::create(['nome' => 'Manhã']);
    $turnoTarde = Turno::create(['nome' => 'Tarde']);
    $cursoClasseTurnoManha = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turnoManha->id,
    ]);
    $cursoClasseTurnoTarde = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turnoTarde->id,
    ]);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $turmaManha = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurnoManha->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma Manhã',
        'sala' => 'Sala 1',
        'max_alunos' => 30,
    ]);
    $outraTurmaManha = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurnoManha->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma Manhã B',
        'max_alunos' => 30,
    ]);
    $turmaTarde = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurnoTarde->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma Tarde',
        'max_alunos' => 30,
    ]);

    $userProfessor = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professor = Professor::create([
        'user_id' => $userProfessor->id,
        'especialidade' => 'Matemática',
    ]);
    $userOutroProfessor = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $outroProfessor = Professor::create([
        'user_id' => $userOutroProfessor->id,
        'especialidade' => 'Física',
    ]);
    $disciplinaMatematica = Disciplina::create(['nome' => 'Matemática', 'sigla' => 'MAT']);
    $disciplinaFisica = Disciplina::create(['nome' => 'Física', 'sigla' => 'FIS']);
    $disciplinaManha = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurnoManha->id,
        'disciplina_id' => $disciplinaMatematica->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);
    $disciplinaTarde = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurnoTarde->id,
        'disciplina_id' => $disciplinaFisica->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'carga_horaria' => '4',
        'tem_professor' => true,
    ]);
    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $disciplinaManha->id,
        'turma_id' => $turmaManha->id,
        'professor_id' => $professor->id,
    ]);
    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $disciplinaManha->id,
        'turma_id' => $outraTurmaManha->id,
        'professor_id' => $professor->id,
    ]);
    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $disciplinaTarde->id,
        'turma_id' => $turmaTarde->id,
        'professor_id' => $professor->id,
    ]);
    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $disciplinaTarde->id,
        'turma_id' => $turmaTarde->id,
        'professor_id' => $outroProfessor->id,
    ]);
    ClasseTurnoDisciplinaHorario::create([
        'classe_turno_disciplina_id' => $disciplinaManha->id,
        'turma_id' => $turmaManha->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '09:00',
    ]);
    ClasseTurnoDisciplinaHorario::create([
        'classe_turno_disciplina_id' => $disciplinaManha->id,
        'turma_id' => $outraTurmaManha->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '09:00',
    ]);
    ClasseTurnoDisciplinaHorario::create([
        'classe_turno_disciplina_id' => $disciplinaTarde->id,
        'turma_id' => $turmaTarde->id,
        'dia_semana' => 1,
        'hora_inicio' => '14:00',
        'hora_fim' => '15:00',
    ]);

    $director = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $director->assignRole(Role::create(['name' => 'Director', 'guard_name' => 'tenant']));
    $outraInstituicao = Instituicao::create(['nome' => 'Outra Instituição']);
    $userProfessorExterno = User::factory()->create(['instituicao_id' => $outraInstituicao->id]);
    Professor::create([
        'user_id' => $userProfessorExterno->id,
        'especialidade' => 'Química',
    ]);

    $response = $this->actingAs($director, 'tenant')->get(route('tenant.dashboard.horarios', [
        'ano_lectivo_id' => $anoLectivo->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tenant/horarios/index')
        ->where('perfil', 'director')
        ->has('professores', 2)
        ->where('professorSeleccionado', null)
    );

    $horarioResponse = $this->actingAs($director, 'tenant')->get(route('tenant.dashboard.horarios', [
        'ano_lectivo_id' => $anoLectivo->id,
        'professor_id' => $professor->id,
    ]));

    $horarioResponse->assertOk();
    $horarioResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->has('aulas', 3)
        ->where('professorSeleccionado', $professor->id)
        ->where('aulas.0.disciplina.nome', 'Matemática')
        ->where('aulas.0.turma.curso', 'Curso Teste')
        ->where('aulas.0.turma.turno', 'Manhã')
        ->where('aulas.0.turma.classe', '12ª')
        ->where('aulas.0.sala', 'Sala 1')
        ->where('aulas.0.turma.nome', 'Turma Manhã')
        ->where('aulas.2.turma.turno', 'Tarde')
    );
});

test('users without an allowed role receive 403 when opening schedules', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'Secretaria', 'guard_name' => 'tenant']));

    $response = $this->actingAs($user, 'tenant')->get(route('tenant.dashboard.horarios'));

    $response->assertForbidden();
});
