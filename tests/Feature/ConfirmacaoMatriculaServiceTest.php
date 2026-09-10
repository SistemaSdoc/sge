<?php

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Services\Tenant\ConfirmacaoMatriculaViewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\User;
use App\Notifications\Aluno\MatriculaConfirmadaNotification;
use App\Services\Tenant\ConfirmacaoMatriculaService;
use Illuminate\Support\Facades\Notification;

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

it('disponibiliza apenas turmas do próximo ano e do mesmo curso tutelado', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Teste',
        'sigla' => 'INST',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição',
        'status' => 1,
    ]);
    $nivelEnsino = NivelEnsino::create(['nome' => 'Médio']);

    $classe = Classe::create(['nome' => '10ª Classe', 'nivel_ensino' => 'medio', 'ordem' => 10]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $anoActual = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $anoProximo = AnoLectivo::create([
        'data_inicio' => now()->addYear()->startOfYear(),
        'data_fim' => now()->addYear()->endOfYear(),
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $cursoClasse = CursoClasse::create([
        'classe_id' => $classe->id,
        'curso_tutelado_id' => $cursoTutelado->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $turmaActual = Turma::create([
        'nome' => '10A',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoActual->id,
    ]);
    $turmaProxima = Turma::create([
        'nome' => '10B',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoProximo->id,
    ]);

    $opcoes = app(ConfirmacaoMatriculaViewService::class)->opcoes($turmaActual, $instituicao);

    expect($opcoes['ano']['id'])->toBe($anoProximo->id)
        ->and($opcoes['turmas']->pluck('id')->all())->toBe([$turmaProxima->id]);
});

it('envia notificação por email quando a matrícula do aluno é confirmada', function () {
    Notification::fake();

    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Teste',
        'sigla' => 'INST',
        'tipo' => 'instituto',
        'email' => 'teste@example.com',
        'telefone' => '123456789',
        'provincia' => 'Luanda',
        'endereco' => 'Rua de Teste',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição',
        'status' => 1,
    ]);

    $classeAtual = Classe::create([
        'nome' => '11ª Classe',
        'ordem' => 11,
    ]);

    $classeNova = Classe::create([
        'nome' => '12ª Classe',
        'ordem' => 12,
    ]);

    $turno = Turno::create([
        'nome' => 'Manhã',
    ]);

    $anoAtual = AnoLectivo::create([
        'nome' => '2025/2026',
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addMonths(10),
        'activo' => true,
    ]);

    $anoProximo = AnoLectivo::create([
        'nome' => '2026/2027',
        'data_inicio' => now()->addMonths(11),
        'data_fim' => now()->addYear()->addMonths(10),
        'activo' => false,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $cursoClasseAtual = CursoClasse::create([
        'classe_id' => $classeAtual->id,
        'curso_tutelado_id' => $cursoTutelado->id,
    ]);

    $cursoClasseNova = CursoClasse::create([
        'classe_id' => $classeNova->id,
        'curso_tutelado_id' => $cursoTutelado->id,
    ]);

    $cursoClasseTurnoAtual = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseAtual->id,
        'turno_id' => $turno->id,
    ]);

    $cursoClasseTurnoNova = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseNova->id,
        'turno_id' => $turno->id,
    ]);

    $turmaAtual = Turma::create([
        'nome' => '11A',
        'curso_classe_turno_id' => $cursoClasseTurnoAtual->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoAtual->id,
    ]);

    $turmaNova = Turma::create([
        'nome' => '12A',
        'curso_classe_turno_id' => $cursoClasseTurnoNova->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoProximo->id,
    ]);

    $candidato = Candidato::create([
        'nome' => 'Aluno confirmado',
        'bi' => '000000000LA03',
        'numero_estudante' => '0003',
        'morada' => 'Rua C',
        'telefone' => '933333333',
        'email' => 'confirmado@example.com',
    ]);

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurnoAtual->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoAtual->id,
        'status' => 'aprovado',
    ]);

    $user = User::factory()->create([
        'nome' => 'Aluno confirmado',
        'email' => 'confirmado@example.com',
    ]);

    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => $user->id,
        'matricula' => 'MAT-CONFIRMADA',
        'situacao' => 'activo',
    ]);

    TurmaAluno::create([
        'turma_id' => $turmaAtual->id,
        'aluno_id' => $aluno->id,
        'ano_lectivo_id' => $anoAtual->id,
        'activo' => true,
        'situacao' => 'activo',
    ]);

    $service = app(ConfirmacaoMatriculaService::class);
    $service->confirmarMatricula($aluno, $turmaNova, $turmaAtual);

    Notification::assertSentTo($user, MatriculaConfirmadaNotification::class, function ($notification, $channels) use ($aluno) {
        return in_array('mail', $channels, true)
            && $notification->aluno->is($aluno)
            && $notification->turmaNova->is($turmaNova);
    });
});
