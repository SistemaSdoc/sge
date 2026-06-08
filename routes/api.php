<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ClasseController as ClasseControllerGeral;
use App\Http\Controllers\ClasseTurnoDisciplinaController;
use App\Http\Controllers\ClasseTurnoDisciplinaHorarioController;
use App\Http\Controllers\ClasseTurnoTurmaController;
use App\Http\Controllers\CursoClasseController;
use App\Http\Controllers\CursoClasseTurnoController;
use App\Http\Controllers\CursosController;
use App\Http\Controllers\CursoTuteladoController;
use App\Http\Controllers\CursoTuteladoProfessorController;
use App\Http\Controllers\Dashboards\DashboardAlunoController;
use App\Http\Controllers\Dashboards\DashboardDirectorController;
use App\Http\Controllers\Dashboards\DashboardProfessorController;
use App\Http\Controllers\DisciplinaController as DisciplinaControllerGeral;
use App\Http\Controllers\ExportarMiniPautaController;
use App\Http\Controllers\ExportarPautaController;
use App\Http\Controllers\FinalistaController;
use App\Http\Controllers\FolhaAprovacaoController;
use App\Http\Controllers\GrupoPapController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\InstituicaoCurso\TurmaController;
use App\Http\Controllers\InstituicaoCurso\TurmaDisciplinaProfessorController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\ProfessorController as ProfessorControllerGeral;
use App\Http\Controllers\ProgressaoController;
use App\Http\Controllers\TurmaController as TurmaControllerGeral;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\TutelaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');

    Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])->middleware('guest');
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);

    Route::get('facebook/redirect', [FacebookAuthController::class, 'redirect'])->middleware('guest');
    Route::get('facebook/callback', [FacebookAuthController::class, 'callback']);
});

Route::middleware('auth:api')->group(function () {
    // Recursos
    Route::resource('instituicoes', InstituicaoController::class)->parameters(['instituicoes' => 'instituicao']);
    Route::resource('users', UserController::class);
    Route::resource('cursos', CursosController::class);
    Route::resource('classes', ClasseControllerGeral::class)->parameters(['classes' => 'classe']);
    Route::resource('turnos', TurnoController::class);
    Route::resource('disciplinas', DisciplinaControllerGeral::class);
    Route::resource('alunos', AlunoController::class);
    Route::resource('inscricoes', InscricaoController::class)->parameters(['inscricoes' => 'inscricao']);
    Route::resource('professores', ProfessorControllerGeral::class)->parameters(['professores' => 'professor']);
    Route::resource('disciplinas', DisciplinaControllerGeral::class);
    Route::resource('tutelas', TutelaController::class)->parameters(['tutelas' => 'cursoTutelado']);
    Route::get('/certificados/{aluno}', [CertificadoController::class, 'show'])->name('certificados.show');

    Route::get('/alunos/{aluno}/turmas-disponiveis', [AlunoController::class, 'turmasDisponiveis']);
    Route::get('/cursos/{curso}/instituicoes-tutoras', [CursosController::class, 'instituicoesTutoras']);
    Route::get('/turmas/{turma}/pauta', [NotaController::class, 'pauta']);
    Route::get('turmas/{turma}/pauta/excel', [ExportarPautaController::class, 'exportarExcel']);

    Route::prefix('dashboard')->group(function () {
        Route::prefix('aluno')->group(function () {
            Route::get('proximas-aulas', [DashboardAlunoController::class, 'proximasAulas']);
            Route::get('resumo-academico', [DashboardAlunoController::class, 'resumoAcademico']);
            Route::get('avisos', [DashboardAlunoController::class, 'avisos']);
        });

        Route::prefix('professor')->group(function () {
            Route::get('proximas-aulas', [DashboardProfessorController::class, 'proximasAulas']);
            Route::get('resumo-academico', [DashboardProfessorController::class, 'resumoAcademico']);
            Route::get('avisos', [DashboardProfessorController::class, 'avisos']);
        });

        Route::prefix('director')->group(function () {
            Route::get('metricas', [DashboardDirectorController::class, 'metricas']);
            Route::get('accoes-pendentes', [DashboardDirectorController::class, 'accoesPendentes']);
            Route::get('avisos', [DashboardDirectorController::class, 'avisos']);
        });
    });

    Route::prefix('instituicoes/{instituicao}')->group(function () {
        Route::get('alunos/{aluno}/historico', [FinalistaController::class, 'historico']);
        Route::get('colegios', [CursoTuteladoController::class, 'colegios']);
        Route::get('turmas', [TurmaControllerGeral::class, 'index']);
        Route::get('aluno/grelha-curricular', [AlunoController::class, 'grelhaCurricular']);
        Route::get('aluno/notas', [AlunoController::class, 'notas']);

        Route::resource('cursos-tutelados', CursoTuteladoController::class)->parameters(['cursos-tutelados' => 'cursoTutelado']);

        Route::prefix('cursos-tutelados/{cursoTutelado}')->group(function () {
            Route::resource('professores', CursoTuteladoProfessorController::class)->only(['index', 'store', 'destroy']);

            Route::resource('classes', CursoClasseController::class)->only(['show'])->parameters(['classes' => 'cursoClasse']);

            Route::resource('turmas', TurmaController::class);

            Route::get('/alunos', [CursoTuteladoController::class, 'alunos']);

            Route::prefix('turmas/{turma}')->group(function () {
                Route::get('progressao/preview', [ProgressaoController::class, 'preview']);
                Route::post('progressao', [ProgressaoController::class, 'store']);
                Route::post('progressao/recurso', [ProgressaoController::class, 'storeRecurso']);
                Route::get('finalistas', [FinalistaController::class, 'index']);
                Route::post('alunos/{aluno}/pap-concluido', [FinalistaController::class, 'papConcluido']);
                Route::post('alunos/{aluno}/concluir', [FinalistaController::class, 'concluir']);
                Route::post('alunos/{aluno}/reprovar', [FinalistaController::class, 'reprovar']);
                Route::post('alunos/{aluno}/desistente', [FinalistaController::class, 'marcarDesistente']);
                // ver a pauta dos colegios tutelados
                Route::get('/pauta', [NotaController::class, 'pauta']);
                // gerar certificado de conclusão do curso dos colegios tutelados
                Route::get('/alunos/{aluno}/certificado', [CertificadoController::class, 'gerarTutora']);
            });

            Route::get('classes-turnos', [CursoClasseTurnoController::class, 'index']);

            Route::put('classes/{cursoClasse}/turnos', [CursoClasseTurnoController::class, 'update']);

            Route::prefix('classes/{cursoClasse}/turnos/{cursoClasseTurno}')->group(function () {
                Route::resource('disciplinas', ClasseTurnoDisciplinaController::class);
                Route::resource('turmas', ClasseTurnoTurmaController::class);

                Route::prefix('turmas/{turma}')->group(function () {
                    Route::post('pap/grupos', [GrupoPapController::class, 'store']);
                    Route::get('pap/alunos-disponiveis', [GrupoPapController::class, 'alunosDisponiveis']);
                    Route::post('pauta/excel', [ExportarPautaController::class, 'exportarExcel']);

                    Route::resource('disciplinas', ClasseTurnoDisciplinaController::class)->only(['index', 'update', 'destroy'])
                        ->parameters(['disciplinas' => 'classeTurnoDisciplina']);

                    Route::prefix('disciplinas/{classeTurnoDisciplina}')->group(function () {
                        Route::post('horarios', [ClasseTurnoDisciplinaHorarioController::class, 'store']);

                        Route::resource('notas', NotaController::class);
                        Route::get('mini-pauta/excel', [ExportarMiniPautaController::class, 'exportarDisciplina']);
                        Route::resource('professores', TurmaDisciplinaProfessorController::class);
                    });

                    Route::get('progressao/preview', [ProgressaoController::class, 'preview']);
                    Route::post('progressao', [ProgressaoController::class, 'store']);

                    Route::prefix('alunos/{aluno}')->group(function () {
                        Route::get('certificado', [CertificadoController::class, 'gerar'])->withoutScopedBindings();
                        // Route::get('certificado', [CertificadoController::class, 'gerarTutora']);
                    });
                });
            });
        });
    });

    Route::prefix('pap')->group(function () {
        Route::get('grupos', [GrupoPapController::class, 'index']);
        Route::put('grupos/{grupoPap}', [GrupoPapController::class, 'update']);
        Route::delete('grupos/{grupoPap}', [GrupoPapController::class, 'destroy']);
        Route::post('grupos/{grupoPap}/elementos', [GrupoPapController::class, 'adicionarElemento']);
        Route::get('grupos/{grupoPap}', [GrupoPapController::class, 'show']);
        Route::put('grupos/{grupoPap}/data-defesa', [GrupoPapController::class, 'definirData']);
        Route::post('grupos/{grupoPap}/banca', [GrupoPapController::class, 'adicionarJurado']);
        Route::delete('grupos/{grupoPap}/banca/{juradoId}', [GrupoPapController::class, 'removerJurado']);
        Route::put('grupos/{grupoPap}/elementos/{elementoGrupoPap}/nota', [GrupoPapController::class, 'actualizarNota']);
        Route::get('grupos/{grupoPap}/folha-aprovacao', [FolhaAprovacaoController::class, 'folhaAprovacao']);
        Route::get('alunos-disponiveis', [GrupoPapController::class, 'alunosDisponiveis']);
    });

    Route::get('turmas/{turma}/pauta/recurso', [NotaController::class, 'pautaRecurso']);
    Route::post('turmas/{turma}/notas/recurso', [NotaController::class, 'storeRecurso']);

    Route::get('avisos', [AvisoController::class, 'index']);
    Route::get('avisos/{aviso}', [AvisoController::class, 'show']);
    Route::post('avisos', [AvisoController::class, 'store']);
    Route::put('avisos/{aviso}', [AvisoController::class, 'update']);
    Route::delete('avisos/{aviso}', [AvisoController::class, 'destroy']);

    // Card do aluno
    Route::get('aluno/avisos', [AvisoController::class, 'indexAluno']);

    // Card do professor
    Route::get('professor/avisos', [AvisoController::class, 'indexProfessor']);

});
