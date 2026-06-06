<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ClasseTurnoDisciplinaController;
use App\Http\Controllers\ClasseTurnoTurmaController;
use App\Http\Controllers\CursoClasseController;
use App\Http\Controllers\CursoClasseTurnoController;
use App\Http\Controllers\CursosController;
use App\Http\Controllers\CursoTuteladoController;
use App\Http\Controllers\CursoTuteladoProfessorController;
use App\Http\Controllers\ExportarMiniPautaController;
use App\Http\Controllers\ExportarPautaController;
use App\Http\Controllers\FinalistaController;
use App\Http\Controllers\GrupoPapController;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ProgressaoController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\TurmaController as TurmaControllerGeral;
use App\Http\Controllers\TurnoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::resource('instituicoes', InstituicaoController::class)->parameters(['instituicoes' => 'instituicao']);
Route::resource('classes', ClasseController::class)->parameters(['classes' => 'classe']);
Route::resource('cursos', CursosController::class);
Route::resource('turnos', TurnoController::class);
Route::resource('professores', ProfessorController::class)->parameters(['professores' => 'professor']);

Route::prefix('instituicoes/{instituicao}')->group(function () {
    Route::get('alunos/{aluno}/historico', [FinalistaController::class, 'historico']);
    Route::get('colegios', [CursoTuteladoController::class, 'colegios']);
    Route::get('turmas', [TurmaControllerGeral::class, 'index']);
    Route::get('aluno/grelha-curricular', [AlunoController::class, 'grelhaCurricular']);
    Route::get('aluno/notas', [AlunoController::class, 'notas']);

    Route::resource('cursos-tutelados', CursoTuteladoController::class)->parameters(['cursos-tutelados' => 'cursoTutelado']);

    Route::prefix('cursos-tutelados/{cursoTutelado}')->group(function () {
        Route::apiResource('professores', CursoTuteladoProfessorController::class)->only(['index', 'store', 'destroy']);

        Route::apiResource('classes', CursoClasseController::class)
            ->only(['show'])
            ->parameters(['classes' => 'cursoClasse'])
            ->names(['show' => 'cursos-tutelados.classes.show']);

        Route::apiResource('turmas', TurmaController::class);

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

                Route::apiResource('disciplinas', ClasseTurnoDisciplinaController::class)->only(['index', 'update', 'destroy'])
                    ->parameters(['disciplinas' => 'classeTurnoDisciplina']);

                Route::prefix('disciplinas/{classeTurnoDisciplina}')->group(function () {
                    // Route::post('horarios', [ClasseTurnoDisciplinaHorarioController::class, 'store']);

                    Route::apiResource('notas', NotaController::class);
                    Route::get('mini-pauta/excel', [ExportarMiniPautaController::class, 'exportarDisciplina']);
                    // Route::apiResource('professores', TurmaDisciplinaProfessorController::class);
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

require __DIR__.'/auth.php';

require __DIR__.'/settings.php';
