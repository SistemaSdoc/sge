<?php

use App\Actions\Tenant\ConfirmacaoMatricula\ConfirmarMatricula;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;

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

function criarContextoConfirmacao(int $ordemClasse = 10): array
{
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste', 'sigla' => 'INST', 'tipo' => 'instituto', 'status' => 1,
    ]);
    $curso = Curso::create([
        'nome' => 'Curso Teste', 'duracao_anos' => 3, 'descricao' => 'Descrição', 'status' => 1,
    ]);
    $nivelEnsino = NivelEnsino::create(['nome' => 'Médio']);
    $classe = Classe::create([
        'nome' => "{$ordemClasse}ª Classe",
        'nivel_ensino' => 'medio',
        'ordem' => $ordemClasse,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $anoActual = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(), 'data_fim' => now()->endOfYear(),
    ]);
    $anoProximo = AnoLectivo::create([
        'data_inicio' => now()->addYear()->startOfYear(), 'data_fim' => now()->addYear()->endOfYear(),
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
    $turmaActual = Turma::create([
        'nome' => '10A', 'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 30, 'ano_lectivo_id' => $anoActual->id,
    ]);
    $turmaProxima = Turma::create([
        'nome' => '10B', 'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 30, 'ano_lectivo_id' => $anoProximo->id,
    ]);
    $candidato = Candidato::create([
        'nome' => 'Aluno Teste', 'bi' => fake()->unique()->numerify('##########'),
        'numero_estudante' => fake()->unique()->numerify('####'),
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id, 'ano_lectivo_id' => $anoActual->id, 'status' => 'aprovado',
    ]);
    $user = User::create([
        'nome' => 'Aluno Teste', 'email' => fake()->unique()->safeEmail(),
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id, 'user_id' => $user->id,
        'matricula' => fake()->unique()->numerify('MAT-####'), 'situacao' => 'activo',
    ]);
    $turmaAluno = TurmaAluno::create([
        'turma_id' => $turmaActual->id, 'aluno_id' => $aluno->id,
        'ano_lectivo_id' => $anoActual->id, 'activo' => true, 'situacao' => 'activo',
    ]);

    Auth::guard('tenant')->login($user);

    return compact('instituicao', 'aluno', 'turmaActual', 'turmaProxima', 'turmaAluno', 'cursoTutelado', 'turno', 'anoProximo', 'nivelEnsino');
}

it('confirma retenção no ano seguinte sem duplicar a associação', function () {
    $contexto = criarContextoConfirmacao();
    /** @var MockInterface&RegraAcademicaService $regra */
    $regra = Mockery::mock(RegraAcademicaService::class);
    $regra->shouldReceive('resolverSituacaoAcademica')->andReturn(['situacao' => 'retido']);
    app()->instance(RegraAcademicaService::class, $regra);

    $action = app(ConfirmarMatricula::class);
    $action->handle(
        $contexto['instituicao'],
        $contexto['aluno'],
        $contexto['turmaProxima'],
        $contexto['turmaActual'],
    );

    expect(TurmaAluno::query()
        ->where('aluno_id', $contexto['aluno']->id)
        ->where('turma_id', $contexto['turmaProxima']->id)
        ->where('activo', true)
        ->count())->toBe(1);

    expect(fn () => $action->handle(
        $contexto['instituicao'], $contexto['aluno'], $contexto['turmaProxima'], $contexto['turmaActual'],
    ))->toThrow(ValidationException::class, 'já confirmou matrícula');
});

it('rejeita uma turma do ano actual', function () {
    $contexto = criarContextoConfirmacao();
    /** @var MockInterface&RegraAcademicaService $regra */
    $regra = Mockery::mock(RegraAcademicaService::class);
    $regra->shouldReceive('resolverSituacaoAcademica')->andReturn(['situacao' => 'retido']);
    app()->instance(RegraAcademicaService::class, $regra);

    expect(fn () => app(ConfirmarMatricula::class)->handle(
        $contexto['instituicao'], $contexto['aluno'], $contexto['turmaActual'], $contexto['turmaActual'],
    ))->toThrow(ValidationException::class, 'próximo ano lectivo');
});

it('rejeita confirmação quando a situação académica ainda está incompleta', function () {
    $contexto = criarContextoConfirmacao();
    /** @var MockInterface&RegraAcademicaService $regra */
    $regra = Mockery::mock(RegraAcademicaService::class);
    $regra->shouldReceive('resolverSituacaoAcademica')->andReturn(['situacao' => 'incompleto']);
    app()->instance(RegraAcademicaService::class, $regra);

    expect(fn () => app(ConfirmarMatricula::class)->handle(
        $contexto['instituicao'], $contexto['aluno'], $contexto['turmaProxima'], $contexto['turmaActual'],
    ))->toThrow(ValidationException::class, 'situação académica');
});

it('rejeita confirmação quando a turma destino está cheia', function () {
    $contexto = criarContextoConfirmacao();
    $contexto['turmaProxima']->update(['max_alunos' => 0]);
    /** @var MockInterface&RegraAcademicaService $regra */
    $regra = Mockery::mock(RegraAcademicaService::class);
    $regra->shouldReceive('resolverSituacaoAcademica')->andReturn(['situacao' => 'retido']);
    app()->instance(RegraAcademicaService::class, $regra);

    expect(fn () => app(ConfirmarMatricula::class)->handle(
        $contexto['instituicao'], $contexto['aluno'], $contexto['turmaProxima'], $contexto['turmaActual'],
    ))->toThrow(ValidationException::class, 'capacidade máxima');
});

it('confirma um aluno que transita apenas na classe seguinte', function () {
    $contexto = criarContextoConfirmacao();
    $classeSeguinte = Classe::create([
        'nome' => '11ª Classe',
        'nivel_ensino' => 'medio',
        'ordem' => 11,
    ]);
    $cursoClasseSeguinte = CursoClasse::create([
        'classe_id' => $classeSeguinte->id,
        'curso_tutelado_id' => $contexto['cursoTutelado']->id,
        'nivel_ensino_id' => $contexto['nivelEnsino']->id,
    ]);
    $turnoSeguinte = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseSeguinte->id,
        'turno_id' => $contexto['turno']->id,
    ]);
    $turmaSeguinte = Turma::create([
        'nome' => '11A', 'curso_classe_turno_id' => $turnoSeguinte->id,
        'max_alunos' => 30, 'ano_lectivo_id' => $contexto['anoProximo']->id,
    ]);
    /** @var MockInterface&RegraAcademicaService $regra */
    $regra = Mockery::mock(RegraAcademicaService::class);
    $regra->shouldReceive('resolverSituacaoAcademica')->andReturn(['situacao' => 'transita']);
    app()->instance(RegraAcademicaService::class, $regra);

    app(ConfirmarMatricula::class)->handle(
        $contexto['instituicao'], $contexto['aluno'], $turmaSeguinte, $contexto['turmaActual'],
    );

    expect(TurmaAluno::query()
        ->where('aluno_id', $contexto['aluno']->id)
        ->where('turma_id', $turmaSeguinte->id)
        ->exists())->toBeTrue();
});
