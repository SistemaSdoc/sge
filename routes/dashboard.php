<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\BancaJuriPapController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ClasseController as ClasseControllerGeral;
use App\Http\Controllers\ClasseTurnoDisciplinaController;
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
use App\Http\Controllers\ElementoGrupoPapController;
use App\Http\Controllers\ExportarPautaController;
use App\Http\Controllers\FinalistaController;
use App\Http\Controllers\FolhaAprovacaoController;
use App\Http\Controllers\GrupoPapController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\InstituicaoCurso\TurmaDisciplinaProfessorController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\ProfessorController as ProfessorControllerGeral;
use App\Http\Controllers\ProgressaoController;
use App\Http\Controllers\TurmaController as TurmaControllerGeral;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\TutelaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Dashboard routes para Staff (Admin, Director, Coordenador, Secretaria, Professor)
// Todas estas rotas requerem autenticação e role de staff
// Middleware 'auth' e 'role:admin,director,coordenador,secretaria,professor' já aplicado no grupo principal

Route::inertia('/', 'dashboard')->name('dashboard');

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
Route::resource('tutelas', TutelaController::class)->parameters(['tutelas' => 'cursoTutelado']);
Route::get('/certificados/{aluno}', [CertificadoController::class, 'show'])->name('certificados.show');

Route::get('/alunos/{aluno}/turmas-disponiveis', [AlunoController::class, 'turmasDisponiveis']);
Route::get('/cursos/{curso}/instituicoes-tutoras', [CursosController::class, 'instituicoesTutoras']);
//Route::get('/turmas/{turma}/pauta', [NotaController::class, 'pauta']);
//Route::get('turmas/{turma}/pauta/excel', [ExportarPautaController::class, 'exportarExcel']);

Route::get('/pautas/cursos', [NotaController::class, 'indexPautas'])->name('pautas.index');

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

Route::prefix('instituicoes/{instituicao}')->group(function () {
    Route::get('alunos/{aluno}/historico', [FinalistaController::class, 'historico']);
    Route::get('colegios', [CursoTuteladoController::class, 'colegios']);
    Route::get('turmas', [TurmaControllerGeral::class, 'index']);
    Route::get('aluno/grelha-curricular', [AlunoController::class, 'grelhaCurricular']);
    Route::get('aluno/notas', [AlunoController::class, 'notas']);

    Route::resource('cursos-tutelados', CursoTuteladoController::class)->parameters(['cursos-tutelados' => 'cursoTutelado']);

    Route::prefix('cursos-tutelados/{cursoTutelado}')->group(function () {
        Route::resource('professores', CursoTuteladoProfessorController::class);

        Route::apiResource('classes', CursoClasseController::class)
            ->only(['show'])
            ->parameters(['classes' => 'cursoClasse'])
            ->names(['show' => 'cursos-tutelados.classes.show']);

        //Route::resource('turmas', ClasseTurnoTurmaController::class);

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
            Route::get('/pauta', [NotaController::class, 'pauta']);
            Route::get('/alunos/{aluno}/certificado', [CertificadoController::class, 'gerarTutora']);
        });

        Route::get('classes-turnos', [CursoClasseTurnoController::class, 'index']);

        Route::put('classes/{cursoClasse}/turnos', [CursoClasseTurnoController::class, 'update']);

        Route::prefix('classes/{cursoClasse}/turnos/{cursoClasseTurno}')->group(function () {
            Route::resource('disciplinas', ClasseTurnoDisciplinaController::class)
                ->parameters(['disciplinas' => 'classeTurnoDisciplina']);
            Route::resource('turmas', ClasseTurnoTurmaController::class);

            Route::prefix('turmas/{turma}')->group(function () {
                Route::get('pap/alunos-disponiveis', [GrupoPapController::class, 'alunosDisponiveis']);

                Route::get('/pauta', [NotaController::class, 'pauta']);
                Route::get('pauta/excel', [ExportarPautaController::class, 'exportarExcel']);
                Route::resource('pap', GrupoPapController::class)->parameters(['pap' => 'grupoPap'])->except('index');

                Route::put('pap/{grupoPap}/data-defesa', [GrupoPapController::class, 'definirData']);

                Route::resource('pap/{grupoPap}/elementos', ElementoGrupoPapController::class)
                    ->parameters(['elementos' => 'elementoGrupoPap'])
                    ->only(['create', 'store', 'destroy']);

                Route::put('pap/{grupoPap}/elementos/{elementoGrupoPap}/nota', [ElementoGrupoPapController::class, 'actualizarNota']);

                Route::get('pap/{grupoPap}/alunos-disponiveis', [ElementoGrupoPapController::class, 'alunosDisponiveis']);

                Route::resource('pap/{grupoPap}/banca', BancaJuriPapController::class)
                    ->parameters(['banca' => 'bancaJuriPap'])
                    ->only(['create', 'store', 'destroy']);

                Route::get('pap/{grupoPap}/folha-aprovacao', [FolhaAprovacaoController::class, 'folhaAprovacao']);

                Route::post('pauta/excel', [ExportarPautaController::class, 'exportarExcel']);

                Route::resource('disciplinas', ClasseTurnoDisciplinaController::class)->only(['index', 'update', 'destroy']);
                Route::get('disciplinas/{classeTurnoDisciplina}/notas', [NotaController::class, 'index']);
                Route::get('disciplinas/{classeTurnoDisciplina}/notas/create', [NotaController::class, 'create']);
                Route::get('disciplinas/{classeTurnoDisciplina}/professores/create', [TurmaDisciplinaProfessorController::class, 'create']);
                Route::post('disciplinas/{classeTurnoDisciplina}/professores', [TurmaDisciplinaProfessorController::class, 'store']);
            });
        });
    });
});

Route::resource('avisos', AvisoController::class);

// Card do aluno
Route::get('aluno/avisos', [AvisoController::class, 'indexAluno']);

// Card do professor
Route::get('professor/avisos', [AvisoController::class, 'indexProfessor']);

Route::prefix('classes/{classe}')->group(function () {
    Route::get('disciplinas', [ClasseControllerGeral::class, 'showDisciplinas']);
    // Route::resource('turnos', TurnoController::class);
});

Route::get('pap', [GrupoPapController::class, 'index']);
