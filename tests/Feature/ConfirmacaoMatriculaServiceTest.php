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
