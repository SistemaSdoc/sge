<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AnoLectivoController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\BancaJuriPapController;
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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclaracaoController;
use App\Http\Controllers\DisciplinaController as DisciplinaControllerGeral;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\ElementoGrupoPapController;
use App\Http\Controllers\FinalistaController;
use App\Http\Controllers\FolhaAprovacaoController;
use App\Http\Controllers\GrelhaCurricularController;
use App\Http\Controllers\GrupoPapAprovacaoController;
use App\Http\Controllers\GrupoPapController;
use App\Http\Controllers\GrupoPapTemaController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\InstituicaoCurso\TurmaDisciplinaProfessorController;
use App\Http\Controllers\ItemPagavelController;
use App\Http\Controllers\NotaAlunoController;
use App\Http\Controllers\NotaDisciplinaController;
use App\Http\Controllers\NotaDisciplinaRecursoController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PeriodoLancamentoNotasController;
use App\Http\Controllers\PreencherHistoricoController;
use App\Http\Controllers\ProfessorController as ProfessorControllerGeral;
use App\Http\Controllers\ProgressaoController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\RegraAvaliacaoController;
use App\Http\Controllers\RelatorioPropinaController;
use App\Http\Controllers\SolicitacaoEdicaoPautaController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Dashboard routes (Admin, Director, Coordenador, Secretaria, Professor)
// Todas estas rotas requerem autenticação e role de staff
// Middleware 'auth' e 'role:admin,director,coordenador,secretaria,professor' já aplicado no grupo principal

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

require __DIR__.'/modules/pautas.php';
require __DIR__.'/modules/certificado.php';
require __DIR__.'/modules/progressao.php';
require __DIR__.'/modules/notas.php';
require __DIR__.'/modules/acess-management.php';
require __DIR__.'/modules/confirmar-matriculas.php';
require __DIR__.'/modules/historico-aluno.php';

// Recursos
Route::resource('instituicoes', InstituicaoController::class)->parameters(['instituicoes' => 'instituicao']);
Route::resource('users', UserController::class);
Route::resource('cursos', CursosController::class);
Route::resource('classes', ClasseControllerGeral::class)->parameters(['classes' => 'classe']);
Route::resource('turnos', TurnoController::class);
Route::resource('disciplinas', DisciplinaControllerGeral::class);
Route::resource('alunos', AlunoController::class);
Route::prefix('turmas/{aluno}')->name('turmas.')->group(function () {
    Route::get('turmas-disponiveis', [TurmaController::class, 'getTurmasDisponiveis'])
        ->name('turmas-disponiveis');
    Route::post('atribuir', [TurmaController::class, 'atribuirTurma'])
        ->name('atribuir');
});
Route::patch('inscricoes/{inscricao}/reativar', [InscricaoController::class, 'reativar'])
    ->name('inscricoes.reativar');
Route::resource('inscricoes', InscricaoController::class)->parameters(['inscricoes' => 'inscricao']);
Route::resource('professores', ProfessorControllerGeral::class)->parameters(['professores' => 'professor']);
Route::get('/certificados/{aluno}', [CertificadoController::class, 'show'])->name('certificados.show');

Route::get('/alunos/{aluno}/turmas-disponiveis', [AlunoController::class, 'turmasDisponiveis']);
Route::get('/cursos/{curso}/instituicoes-tutoras', [CursosController::class, 'instituicoesTutoras']);

Route::get('/turmas/get-turnos/{cursoClasse}', [TurmaController::class, 'getTurnos'])
    ->name('turmas.get-turnos');

Route::get('turmas', [TurmaController::class, 'index'])->name('turmaGeral');

Route::prefix('instituicoes/{instituicao}')->group(function () {
    Route::get('prazos-lancamento-notas', [PeriodoLancamentoNotasController::class, 'edit'])
        ->name('prazos-lancamento-notas.edit');
    Route::put('prazos-lancamento-notas', [PeriodoLancamentoNotasController::class, 'update'])
        ->name('prazos-lancamento-notas.update');

    require __DIR__.'/modules/colegios.php';

    Route::get('alunos/{aluno}/historico', [FinalistaController::class, 'historico']);
    Route::get('aluno/grelha-curricular', [AlunoController::class, 'grelhaCurricular']);
    Route::get('aluno/notas', [AlunoController::class, 'notas']);

    Route::resource('cursos-tutelados', CursoTuteladoController::class)->parameters(['cursos-tutelados' => 'cursoTutelado']);

    Route::prefix('cursos-tutelados/{cursoTutelado}')->group(function () {
        Route::resource('professores', CursoTuteladoProfessorController::class)
            ->names([
                'index' => 'curso-tutelado.professores.index',
                'create' => 'curso-tutelado.professores.create',
                'store' => 'curso-tutelado.professores.store',
                'show' => 'curso-tutelado.professores.show',
                'edit' => 'curso-tutelado.professores.edit',
                'update' => 'curso-tutelado.professores.update',
                'destroy' => 'curso-tutelado.professores.destroy',
            ]);

        Route::apiResource('classes', CursoClasseController::class)
            ->only(['show'])
            ->parameters(['classes' => 'cursoClasse'])
            ->names(['show' => 'cursos-tutelados.classes.show']);

        Route::prefix('turmas/{turma}')->group(function () {
            Route::post('progressao', [ProgressaoController::class, 'store']);
            // Route::post('progressao/recurso', [ProgressaoController::class, 'storeRecurso']);
            Route::get('disciplinas/{classeTurnoDisciplina}/notas/recurso', [NotaDisciplinaRecursoController::class, 'index']);
            Route::post('disciplinas/{classeTurnoDisciplina}/notas/recurso', [NotaDisciplinaRecursoController::class, 'store']);

            Route::get('finalistas', [FinalistaController::class, 'index']);
            Route::post('alunos/{aluno}/pap-concluido', [FinalistaController::class, 'papConcluido']);
            Route::post('alunos/{aluno}/concluir', [FinalistaController::class, 'concluir']);
            Route::post('alunos/{aluno}/reprovar', [FinalistaController::class, 'reprovar']);
            Route::post('alunos/{aluno}/desistente', [FinalistaController::class, 'marcarDesistente']);
            Route::get('/alunos/{aluno}/certificado', [CertificadoController::class, 'gerarTutora']);

        });

        Route::get('classes-turnos', [CursoClasseTurnoController::class, 'index']);

        Route::get(
            'classes/{cursoClasse}/turnos/create',
            [CursoClasseTurnoController::class, 'create']
        );

        Route::put('classes/{cursoClasse}/turnos', [CursoClasseTurnoController::class, 'store']);

        Route::prefix('classes/{cursoClasse}/turnos/{cursoClasseTurno}')
            ->group(function () {
                Route::resource('disciplinas', ClasseTurnoDisciplinaController::class)
                    ->parameters(['disciplinas' => 'classeTurnoDisciplina'])
                    ->names([
                        'index' => 'classe-turno.disciplinas.index',
                        'create' => 'classe-turno.disciplinas.create',
                        'store' => 'classe-turno.disciplinas.store',
                        'show' => 'classe-turno.disciplinas.show',
                        'edit' => 'classe-turno.disciplinas.edit',
                        'update' => 'classe-turno.disciplinas.update',
                        'destroy' => 'classe-turno.disciplinas.destroy',
                    ]);
                Route::resource('turmas', ClasseTurnoTurmaController::class);

                Route::prefix('turmas/{turma}')->group(function () {
                    Route::get('pap/alunos-disponiveis', [GrupoPapController::class, 'alunosDisponiveis']);
                    Route::get('progressao/preview', [ProgressaoController::class, 'preview']);

                    Route::get('/alunos/{aluno}/declaracao', [DeclaracaoController::class, 'download'])
                        ->name('declaracao.download');

                    Route::resource('pap', GrupoPapController::class)->parameters(['pap' => 'grupoPap'])->except('index');

                    Route::put('pap/{grupoPap}/data-defesa', [GrupoPapController::class, 'definirData']);
                    Route::get('{grupoPap}/tema/create', [GrupoPapTemaController::class, 'create'])->name('tema.create');
                    Route::post('{grupoPap}/tema', [GrupoPapTemaController::class, 'store'])->name('tema.store');
                    Route::get('{grupoPap}/tema/edit', [GrupoPapTemaController::class, 'edit'])->name('tema.edit');
                    Route::put('{grupoPap}/tema', [GrupoPapTemaController::class, 'update'])->name('tema.update');

                    Route::resource('pap/{grupoPap}/elementos', ElementoGrupoPapController::class)
                        ->parameters(['elementos' => 'elementoGrupoPap'])
                        ->only(['create', 'store', 'destroy']);

                    Route::put('pap/{grupoPap}/elementos/{elementoGrupoPap}/nota', [ElementoGrupoPapController::class, 'actualizarNota']);

                    Route::get('pap/{grupoPap}/alunos-disponiveis', [ElementoGrupoPapController::class, 'alunosDisponiveis']);

                    Route::resource('pap/{grupoPap}/banca', BancaJuriPapController::class)
                        ->parameters(['banca' => 'bancaJuriPap'])
                        ->only(['create', 'store', 'edit', 'update', 'destroy']);

                    Route::get('pap/{grupoPap}/folha-aprovacao', [FolhaAprovacaoController::class, 'folhaAprovacao']);

                    Route::resource('disciplinas', ClasseTurnoDisciplinaController::class)
                        ->only(['index', 'update', 'destroy'])
                        ->parameters(['disciplinas' => 'classeTurnoDisciplina'])
                        ->names([
                            'index' => 'turma.disciplinas.index',
                            'update' => 'turma.disciplinas.update',
                            'destroy' => 'turma.disciplinas.destroy',
                        ]);
                    Route::get('disciplinas/{classeTurnoDisciplina}/notas', [NotaDisciplinaController::class, 'index']);
                    Route::get('disciplinas/{classeTurnoDisciplina}/notas/create', [NotaDisciplinaController::class, 'create']);
                    Route::post('disciplinas/{classeTurnoDisciplina}/notas', [NotaDisciplinaController::class, 'store']);
                    Route::get('disciplinas/{classeTurnoDisciplina}/professores/create', [TurmaDisciplinaProfessorController::class, 'create']);
                    Route::post('disciplinas/{classeTurnoDisciplina}/professores', [TurmaDisciplinaProfessorController::class, 'store']);
                    Route::post('disciplinas/{classeTurnoDisciplina}/horarios', [ClasseTurnoDisciplinaHorarioController::class, 'store']);
                });
            });
    });
});

Route::resource('avisos', AvisoController::class);
Route::resource('anos-lectivos', AnoLectivoController::class)->parameters(['anos-lectivos' => 'anoLectivo']);
Route::get('pap', [GrupoPapController::class, 'index'])->name('grupos-pap.index');

Route::middleware('propina.em.dia')->group(function () {
    Route::get('minhas-notas', [NotaAlunoController::class, 'index'])->name('notas.aluno.index');
    Route::get('grelha-curricular', [GrelhaCurricularController::class, 'index'])->name('grelha-curricular.index');
});

Route::get('turmas/{turma}/relatorio-propinas', [RelatorioPropinaController::class, 'porTurma'])
    ->name('turmas.relatorio-propinas');

Route::get('turmas/{turma}/relatorio-propinas/pdf', [RelatorioPropinaController::class, 'pdf'])
    ->name('turmas.relatorio-propinas.pdf');

Route::resource('regras-avaliacao', RegraAvaliacaoController::class)
    ->parameters(['regras-avaliacao' => 'regraAvaliacao']);

Route::resource('itens-pagaveis', ItemPagavelController::class)
    ->names([
        'index' => 'itens-pagaveis.index',
        'create' => 'itens-pagaveis.create',
        'store' => 'itens-pagaveis.store',
        'show' => 'itens-pagaveis.show',
        'edit' => 'itens-pagaveis.edit',
        'update' => 'itens-pagaveis.update',
        'destroy' => 'itens-pagaveis.destroy',
    ])
    ->parameters(['itens-pagaveis' => 'itemPagavel']);

Route::resource('pagamentos', PagamentoController::class)
    ->names([
        'index' => 'pagamentos.index',
        'create' => 'pagamentos.create',
        'store' => 'pagamentos.store',
        'show' => 'pagamentos.show',
        'edit' => 'pagamentos.edit',
        'update' => 'pagamentos.update',
        'destroy' => 'pagamentos.destroy',
    ])
    ->parameters(['pagamentos' => 'pagamento']);

Route::get('notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
Route::post('notificacoes/{id}/ler', [NotificacaoController::class, 'marcarLida'])->name('notificacoes.ler');
Route::post('notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodasLidas'])->name('notificacoes.ler-todas');

Route::get('/pagamentos/{pagamento}/recibo', [ReciboController::class, 'exibir'])
    ->name('pagamentos.recibo');

Route::get('/pagamentos/{pagamento}/recibo/exportar', [ReciboController::class, 'exportar'])
        ->name('pagamentos.recibo.exportar');

Route::get('/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/turmas/{turma}/pauta', [CursoTuteladoController::class, 'pauta'])
    ->name('pauta');

Route::get('/pautas/solicitacoes', [SolicitacaoEdicaoPautaController::class, 'index'])
    ->name('pautas.solicitacoes.index');
Route::post('/pautas/solicitacoes/{solicitacao}/decidir', [SolicitacaoEdicaoPautaController::class, 'decidir'])
    ->name('pautas.solicitacoes.decidir');

Route::prefix('grupo-pap-aprovacao')
    ->name('grupo-pap-aprovacao.')
    ->group(function () {
        Route::get('/pendentes', [GrupoPapAprovacaoController::class, 'pendentes'])
            ->name('pendentes');

        Route::post('/{grupoPap}/aprovar', [GrupoPapAprovacaoController::class, 'aprovar'])
            ->name('aprovar');

        Route::post('/{grupoPap}/reprovar', [GrupoPapAprovacaoController::class, 'reprovar'])
            ->name('reprovar');

        Route::post('/{grupoPap}/solicitar-melhoria', [GrupoPapAprovacaoController::class, 'solicitarMelhoria'])
            ->name('solicitar-melhoria');
    });

Route::post('/grupo-pap-aprovacao/{grupoPap}/aprovar-tutor', [GrupoPapAprovacaoController::class, 'aprovarTutor'])
    ->name('grupo-pap-aprovacao.aprovar-tutor');

Route::post(
    'grupo-pap/{grupoPap}/solicitar-melhoria-tutor',
    [GrupoPapAprovacaoController::class, 'solicitarMelhoriaComoTutor']
)->name('grupo-pap-aprovacao.solicitar-melhoria-tutor');

Route::get('/grupo-pap-aprovacao/melhorias', [GrupoPapAprovacaoController::class, 'melhorias'])
    ->name('grupo-pap-aprovacao.melhorias');

Route::get(
    '/grupo-pap-aprovacao/melhorias',
    [GrupoPapAprovacaoController::class, 'melhorias']
)->name(
    'grupo-pap-aprovacao.melhorias'
);

Route::get(
    '/grupo-pap-aprovacao/{grupoPap}/editar',
    [GrupoPapAprovacaoController::class, 'editar']
)->name(
    'grupo-pap-aprovacao.editar'
);

Route::put(
    '/grupo-pap-aprovacao/{grupoPap}',
    [GrupoPapAprovacaoController::class, 'atualizar']
)->name(
    'grupo-pap-aprovacao.atualizar'
);

Route::put(
    '/grupo-pap-aprovacao/{grupoPap}/reenviar',
    [GrupoPapAprovacaoController::class, 'reenviar']
)->name(
    'grupo-pap-aprovacao.reenviar'
);

Route::get(
    '/grupo-pap-aprovacao/{grupoPap}/historico',
    [GrupoPapAprovacaoController::class, 'historico']
)->name(
    'grupo-pap-aprovacao.historico'
);

Route::get('/grupo-pap-aprovacao/{grupoPap}/historico', [GrupoPapAprovacaoController::class, 'historico'])
    ->name('grupo-pap-aprovacao.historico');

Route::inertia('horarios', 'horarios/index')->name('horarios');

Route::inertia('propinas/bloqueio', 'propinas/bloqueio')->name('propinas.divida');

// ─── Histórico Académico ────────────────────────────────────────────────────

Route::prefix('historico/{aluno}')
    ->name('preencher-historico.')
    ->group(function () {
        // GET  /historico/{aluno}/lancar?turma_aluno_id=...
        // Formulário de lançamento das notas históricas
        Route::get('lancar', [PreencherHistoricoController::class, 'create'])
            ->name('create');

        // POST /historico/{aluno}/lancar
        // Guarda ou finaliza as notas do trimestre seleccionado
        Route::post('lancar', [PreencherHistoricoController::class, 'store'])
            ->name('store');

        // POST /historico/{aluno}/confirmar
        // Cria o TurmaAluno histórico e redireciona para o create
        Route::post('confirmar', [PreencherHistoricoController::class, 'confirmar'])
            ->name('confirmar');
    });

Route::post(
    'instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/criterios-pap',
    [CursoTuteladoController::class, 'uploadCriteriosPap']
)->name('cursos-tutelados.criterios-pap');
Route::get(
    'documentos/pesquisar-aluno',
    [DocumentosController::class, 'pesquisarAluno']
)->name('documentos.pesquisar-aluno');

Route::match(
    ['GET', 'POST'],
    'documentos/exportar',
    [DocumentosController::class, 'exportar']
)->name('documentos.exportar');

Route::get(
    'documentos',
    [DocumentosController::class, 'index']
)->name('documentos.index');
