<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\ConfirmacaoMatriculaViewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

it('returns incompleto when there are missing final grades on the confirmation list', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste', 'sigla' => 'INST', 'tipo' => 'instituto', 'status' => 1,
    ]);
    $curso = Curso::create([
        'nome' => 'Curso Teste', 'duracao_anos' => 3, 'descricao' => 'Descrição', 'status' => 1,
    ]);
    $nivelEnsino = NivelEnsino::create(['nome' => 'Médio']);
    $classe = Classe::create(['nome' => '10ª Classe', 'nivel_ensino' => 'medio', 'ordem' => 10]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(), 'data_fim' => now()->endOfYear(),
    ]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id, 'curso_id' => $curso->id, 'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id, 'instituicao_tutora_id' => $instituicao->id,
    ]);
    $cursoClasse = CursoClasse::create([
        'classe_id' => $classe->id, 'curso_tutelado_id' => $cursoTutelado->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id, 'turno_id' => $turno->id,
    ]);
    $disciplina = Disciplina::create([
        'nome' => 'Matemática', 'sigla' => 'MAT',
    ]);
    $professor = Professor::create([
        'user_id' => User::create([
            'nome' => 'Professor Teste', 'email' => fake()->unique()->safeEmail(),
        ])->id,
        'especialidade' => 'Matemática',
    ]);
    $classeTurnoDisciplina = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplina->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'carga_horaria' => 1,
        'tem_professor' => true,
    ]);
    $turma = Turma::create([
        'nome' => '10A', 'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 40, 'ano_lectivo_id' => $anoLectivo->id,
    ]);
    $turmaDisciplinaProfessor = TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
        'turma_id' => $turma->id, 'professor_id' => $professor->id,
    ]);
    $candidato = Candidato::create([
        'nome' => 'Candidato Teste',
        'bi' => fake()->unique()->numerify('##########'),
        'numero_estudante' => fake()->unique()->numerify('####'),
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id, 'ano_lectivo_id' => $anoLectivo->id, 'status' => 'aprovado',
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => User::create([
            'nome' => 'Aluno Teste', 'email' => fake()->unique()->safeEmail(),
        ])->id,
        'matricula' => 'MAT-001', 'situacao' => 'activo',
    ]);
    $turmaAluno = TurmaAluno::create([
        'turma_id' => $turma->id, 'aluno_id' => $aluno->id,
        'activo' => true, 'situacao' => 'activo',
    ]);

    foreach ([1, 2, 3] as $periodo) {
        Nota::create([
            'turma_aluno_id' => $turmaAluno->id,
            'turma_disciplina_professor_id' => $turmaDisciplinaProfessor->id,
            'periodo' => $periodo,
            'media_trimestral' => 12,
        ]);
    }

    $paginator = app(ConfirmacaoMatriculaViewService::class)->listarAlunos($turma);

    expect($paginator->items()[0]['status'])->toBe('incompleto')
        ->and($paginator->items()[0]['can']['confirmar_matricula'])->toBeFalse();
});
