<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AlunoController;
use App\Http\Controllers\Tenant\AnoLectivoController;
use App\Http\Controllers\Tenant\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\AvisoController;
use App\Http\Controllers\Tenant\BancaJuriPapController;
use App\Http\Controllers\Tenant\CertificadoController;
use App\Http\Controllers\Tenant\ClasseController as ClasseControllerGeral;
use App\Http\Controllers\Tenant\ClasseTurnoDisciplinaController;
use App\Http\Controllers\Tenant\ClasseTurnoDisciplinaHorarioController;
use App\Http\Controllers\Tenant\ClasseTurnoTurmaController;
use App\Http\Controllers\Tenant\CursoClasseController;
use App\Http\Controllers\Tenant\CursoClasseTurnoController;
use App\Http\Controllers\Tenant\CursosController;
use App\Http\Controllers\Tenant\CursoTuteladoController;
use App\Http\Controllers\Tenant\CursoTuteladoProfessorController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\DisciplinaController as DisciplinaControllerGeral;
use App\Http\Controllers\Tenant\DocumentosController;
use App\Http\Controllers\Tenant\ElementoGrupoPapController;
use App\Http\Controllers\Tenant\FolhaAprovacaoController;
use App\Http\Controllers\Tenant\GrelhaCurricularController;
use App\Http\Controllers\Tenant\GrupoPapAprovacaoController;
use App\Http\Controllers\Tenant\GrupoPapController;
use App\Http\Controllers\Tenant\GrupoPapTemaController;
use App\Http\Controllers\Tenant\InscricaoController;
use App\Http\Controllers\Tenant\InstituicaoController;
use App\Http\Controllers\Tenant\InstituicaoCurso\TurmaDisciplinaProfessorController;
use App\Http\Controllers\Tenant\ItemPagavelController;
use App\Http\Controllers\Tenant\NotaAlunoController;
use App\Http\Controllers\Tenant\NotaDisciplinaController;
use App\Http\Controllers\Tenant\NotaDisciplinaRecursoController;
use App\Http\Controllers\Tenant\NotificacaoController;
use App\Http\Controllers\Tenant\PagamentoController;
use App\Http\Controllers\Tenant\PeriodoLancamentoNotasController;
use App\Http\Controllers\Tenant\PreencherHistoricoController;
use App\Http\Controllers\Tenant\ProfessorController as ProfessorControllerGeral;
use App\Http\Controllers\Tenant\ReciboController;
use App\Http\Controllers\Tenant\RegraAvaliacaoController;
use App\Http\Controllers\Tenant\RelatorioPropinaController;
use App\Http\Controllers\Tenant\SolicitacaoEdicaoPautaController;
use App\Http\Controllers\Tenant\TurmaController;
use App\Http\Controllers\Tenant\TurnoController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Aqui você pode registrar as rotas de tenant para a aplicação.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Rotas de Autenticação de Tenant
    |--------------------------------------------------------------------------
    */

    Route::get('/', [AuthenticatedSessionController::class, 'create'])
        ->middleware('guest:tenant')
        ->name('tenant.login');

    Route::post('/', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:tenant')
        ->name('tenant.login.store');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:tenant')
        ->name('tenant.logout');

    Route::get('token/{token}', [AuthenticatedSessionController::class, 'token'])
        ->middleware('guest:tenant')
        ->name('tenant.login.token');

    /*
    |--------------------------------------------------------------------------
    | Rota Pública para Consulta do Certificado via QRcode
    |--------------------------------------------------------------------------
    */

    Route::get('/certificados/{aluno}/verificar', [CertificadoController::class, 'show'])
        ->name('certificados.verificar');

    /*
    |--------------------------------------------------------------------------
    | Rotas Internas do Tenant (Dashboard Routes)
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('auth:tenant')
        ->name('tenant.dashboard');

    Route::middleware(['auth:tenant', 'verified', 'role:SuperAdmin|Director|Subdirector|Secretaria|Professor|Aluno'])
        ->prefix('dashboard')
        ->name('tenant.dashboard.')
        ->group(function () {

            require base_path('routes/modules/confirmar-matriculas.php');
            require base_path('routes/modules/acess-management.php');
            require base_path('routes/modules/historico-aluno.php');
            require base_path('routes/modules/certificado.php');
            require base_path('routes/modules/progressao.php');
            require base_path('routes/modules/pautas.php');
            require base_path('routes/modules/notas.php');
            require base_path('routes/settings.php');

            Route::resource('users', UserController::class);
            Route::resource('alunos', AlunoController::class);
            Route::resource('avisos', AvisoController::class);
            Route::resource('anos-lectivos', AnoLectivoController::class)->parameters(['anos-lectivos' => 'anoLectivo']);
            Route::resource('regras-avaliacao', RegraAvaliacaoController::class)->parameters(['regras-avaliacao' => 'regraAvaliacao']);

            /*
            |--------------------------------------------------------------------------
            | Alunos - Ações Adicionais
            |--------------------------------------------------------------------------
            */

            Route::get('alunos/{aluno}/turmas-disponiveis', [AlunoController::class, 'turmasDisponiveis'])
                ->name('alunos.turmas-disponiveis');

            /*
            |--------------------------------------------------------------------------
            | Pagamentos e Itens Pagáveis
            |--------------------------------------------------------------------------
            */

            Route::resource('pagamentos', PagamentoController::class)->parameters(['pagamentos' => 'pagamento']);
            Route::resource('itens-pagaveis', ItemPagavelController::class)->parameters(['itens-pagaveis' => 'itemPagavel']);

            /*
            |--------------------------------------------------------------------------
            | Turmas - Atribuição de Alunos
            |--------------------------------------------------------------------------
            */

            Route::get('turmas/{aluno}/turmas-disponiveis', [TurmaController::class, 'getTurmasDisponiveis'])
                ->name('turmas.turmas-disponiveis');

            Route::post('turmas/{aluno}/atribuir', [TurmaController::class, 'atribuirTurma'])
                ->name('turmas.atribuir');

            Route::get('turmas/get-turnos/{cursoClasse}', [TurmaController::class, 'getTurnos'])
                ->name('turmas.get-turnos');

            Route::get('turmas', [TurmaController::class, 'index'])
                ->name('turmas.index');

            /*
            |--------------------------------------------------------------------------
            | Inscrições
            |--------------------------------------------------------------------------
            */

            Route::resource('inscricoes', InscricaoController::class)->parameters(['inscricoes' => 'inscricao']);

            Route::patch('inscricoes/{inscricao}/reativar', [InscricaoController::class, 'reativar'])
                ->name('inscricoes.reativar');

            /*
            |--------------------------------------------------------------------------
            | Certificados
            |--------------------------------------------------------------------------
            */

            Route::get('certificados/{aluno}', [CertificadoController::class, 'show'])
                ->name('certificados.show');

            /*
            |--------------------------------------------------------------------------
            | Instituições e Prazos de Lançamento de Notas
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes', InstituicaoController::class)->parameters(['instituicoes' => 'instituicao']);

            Route::get('instituicoes/{instituicao}/prazos-lancamento-notas', [PeriodoLancamentoNotasController::class, 'edit'])
                ->name('instituicoes.prazos-lancamento-notas.edit');

            Route::put('instituicoes/{instituicao}/prazos-lancamento-notas', [PeriodoLancamentoNotasController::class, 'update'])
                ->name('instituicoes.prazos-lancamento-notas.update');

            require base_path('routes/modules/colegios.php');

            /*
            |--------------------------------------------------------------------------
            | Cursos Tutelados
            |--------------------------------------------------------------------------
            */

            Route::resource('cursos', CursosController::class);

            Route::resource('instituicoes.cursos-tutelados', CursoTuteladoController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                ]);

            Route::post('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/criterios-pap', [CursoTuteladoController::class, 'uploadCriteriosPap'])
                ->name('instituicoes.cursos-tutelados.criterios-pap');

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/turmas/{turma}/pauta', [CursoTuteladoController::class, 'pauta'])
                ->name('instituicoes.cursos-tutelados.turmas.pauta');

            Route::get('cursos/{curso}/instituicoes-tutoras', [CursosController::class, 'instituicoesTutoras'])
                ->name('cursos.instituicoes-tutoras');

            /*
            |--------------------------------------------------------------------------
            | Professores de Cursos Tutelados
            |--------------------------------------------------------------------------
            */

            Route::resource('professores', ProfessorControllerGeral::class)->parameters(['professores' => 'professor']);

            Route::resource('instituicoes.cursos-tutelados.professores', CursoTuteladoProfessorController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'professores' => 'professor',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Classes de Cursos Tutelados
            |--------------------------------------------------------------------------
            */

            Route::resource('classes', ClasseControllerGeral::class)->parameters(['classes' => 'classe']);

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}', [CursoClasseController::class, 'show'])
                ->name('cursos-tutelados.classes.show');

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes-turnos', [CursoClasseController::class, 'index'])
                ->name('cursos-tutelados.classes-turnos.index');

            /*
            |--------------------------------------------------------------------------
            | Turnos de Classes de Cursos Tutelados
            |--------------------------------------------------------------------------
            */

            Route::resource('turnos', TurnoController::class);

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes-turnos', [CursoClasseTurnoController::class, 'index'])
                ->name('curso-classe-turno.index');

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/create', [CursoClasseTurnoController::class, 'create'])
                ->name('curso-classe-turno.create');

            Route::put('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos', [CursoClasseTurnoController::class, 'store'])
                ->name('curso-classe-turno.store');

            /*
            |--------------------------------------------------------------------------
            | Disciplinas de Turnos de Classes
            |--------------------------------------------------------------------------
            */

            Route::resource('disciplinas', DisciplinaControllerGeral::class);

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.disciplinas', ClasseTurnoDisciplinaController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'disciplinas' => 'classeTurnoDisciplina',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Turmas de Turnos de Classes
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas', ClasseTurnoTurmaController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Notas de Disciplinas
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.disciplinas', ClasseTurnoDisciplinaController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'disciplinas' => 'classeTurnoDisciplina',
                ])->only(['index', 'update', 'destroy']);

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.disciplinas.notas', NotaDisciplinaController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'disciplinas' => 'classeTurnoDisciplina',
                    'notas' => 'notaDisciplina',
                ])->only(['index', 'create', 'store']);

            /*
            |--------------------------------------------------------------------------
            | Notas de Recurso
            |--------------------------------------------------------------------------
            */

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/notas/recurso', [NotaDisciplinaRecursoController::class, 'index'])
                ->name('turma.disciplinas.notas.recurso.index');

            Route::post('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/notas/recurso', [NotaDisciplinaRecursoController::class, 'store'])
                ->name('turma.disciplinas.notas.recurso.store');

            /*
            |--------------------------------------------------------------------------
            | Professores de Disciplinas de Turmas
            |--------------------------------------------------------------------------
            */

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/professores/create', [TurmaDisciplinaProfessorController::class, 'create'])
                ->name('turma.disciplinas.professores.create');

            Route::post('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/professores', [TurmaDisciplinaProfessorController::class, 'store'])
                ->name('turma.disciplinas.professores.store');

            /*
            |--------------------------------------------------------------------------
            | Horários de Disciplinas de Turmas
            |--------------------------------------------------------------------------
            */

            Route::post('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/horarios', [ClasseTurnoDisciplinaHorarioController::class, 'store'])
                ->name('turma.disciplinas.horarios.store');

            /*
            |--------------------------------------------------------------------------
            | Grupos PAP
            |--------------------------------------------------------------------------
            */

            Route::get('pap', [GrupoPapController::class, 'index'])
                ->name('grupos-pap.index');

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/alunos-disponiveis', [GrupoPapController::class, 'alunosDisponiveis'])
                ->name('turma.pap.alunos-disponiveis');

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.pap', GrupoPapController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'pap' => 'grupoPap',
                ]);

            Route::put('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}/data-defesa', [GrupoPapController::class, 'definirData'])
                ->name('turma.pap.definir-data');

            /*
            |--------------------------------------------------------------------------
            | Temas do PAP
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.pap.tema', GrupoPapTemaController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'pap' => 'grupoPap',
                ])->only(['create', 'store', 'edit', 'update']);

            /*
            |--------------------------------------------------------------------------
            | Elementos do PAP (Alunos)
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos', ElementoGrupoPapController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'pap' => 'grupoPap',
                    'elementos' => 'elementoGrupoPap',
                ])->only(['create', 'store', 'destroy']);

            Route::put('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}/elementos/{elementoGrupoPap}/nota', [ElementoGrupoPapController::class, 'actualizarNota'])
                ->name('turma.pap.elementos.atualizar-nota');

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}/alunos-disponiveis', [ElementoGrupoPapController::class, 'alunosDisponiveis'])
                ->name('turma.pap.elementos.alunos-disponiveis');

            /*
            |--------------------------------------------------------------------------
            | Banca de Júri do PAP
            |--------------------------------------------------------------------------
            */

            Route::resource('instituicoes.cursos-tutelados.classes.turnos.turmas.pap.banca', BancaJuriPapController::class)
                ->parameters([
                    'instituicoes' => 'instituicao',
                    'cursos-tutelados' => 'cursoTutelado',
                    'classes' => 'cursoClasse',
                    'turnos' => 'cursoClasseTurno',
                    'turmas' => 'turma',
                    'pap' => 'grupoPap',
                    'banca' => 'bancaJuriPap',
                ])->only(['create', 'store', 'edit', 'update', 'destroy']);

            /*
            |--------------------------------------------------------------------------
            | Folha de Aprovação do PAP
            |--------------------------------------------------------------------------
            */

            Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}/folha-aprovacao', [FolhaAprovacaoController::class, 'folhaAprovacao'])
                ->name('turma.pap.folha-aprovacao');

            /*
            |--------------------------------------------------------------------------
            | Aprovação de Grupos PAP
            |--------------------------------------------------------------------------
            */

            Route::get('grupo-pap-aprovacao/pendentes', [GrupoPapAprovacaoController::class, 'pendentes'])
                ->name('grupo-pap-aprovacao.pendentes');

            Route::post('grupo-pap-aprovacao/{grupoPap}/aprovar', [GrupoPapAprovacaoController::class, 'aprovar'])
                ->name('grupo-pap-aprovacao.aprovar');

            Route::post('grupo-pap-aprovacao/{grupoPap}/reprovar', [GrupoPapAprovacaoController::class, 'reprovar'])
                ->name('grupo-pap-aprovacao.reprovar');

            Route::post('grupo-pap-aprovacao/{grupoPap}/solicitar-melhoria', [GrupoPapAprovacaoController::class, 'solicitarMelhoria'])
                ->name('grupo-pap-aprovacao.solicitar-melhoria');

            Route::post('grupo-pap-aprovacao/{grupoPap}/aprovar-tutor', [GrupoPapAprovacaoController::class, 'aprovarTutor'])
                ->name('grupo-pap-aprovacao.aprovar-tutor');

            Route::post('grupo-pap/{grupoPap}/solicitar-melhoria-tutor', [GrupoPapAprovacaoController::class, 'solicitarMelhoriaComoTutor'])
                ->name('grupo-pap-aprovacao.solicitar-melhoria-tutor');

            Route::get('grupo-pap-aprovacao/{grupoPap}/editar', [GrupoPapAprovacaoController::class, 'editar'])
                ->name('grupo-pap-aprovacao.editar');

            Route::put('grupo-pap-aprovacao/{grupoPap}', [GrupoPapAprovacaoController::class, 'atualizar'])
                ->name('grupo-pap-aprovacao.atualizar');

            Route::put('grupo-pap-aprovacao/{grupoPap}/reenviar', [GrupoPapAprovacaoController::class, 'reenviar'])
                ->name('grupo-pap-aprovacao.reenviar');

            Route::get('grupo-pap-aprovacao/melhorias', [GrupoPapAprovacaoController::class, 'melhorias'])
                ->name('grupo-pap-aprovacao.melhorias');

            Route::get('grupo-pap-aprovacao/{grupoPap}/historico', [GrupoPapAprovacaoController::class, 'historico'])
                ->name('grupo-pap-aprovacao.historico');

            /*
            |--------------------------------------------------------------------------
            | Relatórios de Propinas
            |--------------------------------------------------------------------------
            */

            Route::get('turmas/{turma}/relatorio-propinas', [RelatorioPropinaController::class, 'porTurma'])
                ->name('turmas.relatorio-propinas');

            Route::get('turmas/{turma}/relatorio-propinas/pdf', [RelatorioPropinaController::class, 'pdf'])
                ->name('turmas.relatorio-propinas.pdf');

            /*
            |--------------------------------------------------------------------------
            | Recibos
            |--------------------------------------------------------------------------
            */

            Route::get('pagamentos/{pagamento}/recibo', [ReciboController::class, 'exibir'])
                ->name('pagamentos.recibo');

            Route::get('pagamentos/{pagamento}/recibo/exportar', [ReciboController::class, 'exportar'])
                ->name('pagamentos.recibo.exportar');

            /*
            |--------------------------------------------------------------------------
            | Notificações
            |--------------------------------------------------------------------------
            */

            Route::get('notificacoes', [NotificacaoController::class, 'index'])
                ->name('notificacoes.index');

            Route::post('notificacoes/{id}/ler', [NotificacaoController::class, 'marcarLida'])
                ->name('notificacoes.ler');

            Route::post('notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodasLidas'])
                ->name('notificacoes.ler-todas');

            /*
            |--------------------------------------------------------------------------
            | Notas do Aluno (Visualização com permissão de propina)
            |--------------------------------------------------------------------------
            */

            Route::middleware('propina.em.dia')->group(function () {
                Route::get('minhas-notas', [NotaAlunoController::class, 'index'])
                    ->name('notas.aluno.index');

                Route::get('grelha-curricular', [GrelhaCurricularController::class, 'index'])
                    ->name('grelha-curricular.index');
            });

            /*
            |--------------------------------------------------------------------------
            | Pautas - Solicitações de Edição
            |--------------------------------------------------------------------------
            */

            Route::get('pautas/solicitacoes', [SolicitacaoEdicaoPautaController::class, 'index'])
                ->name('pautas.solicitacoes.index');

            Route::post('pautas/solicitacoes/{solicitacao}/decidir', [SolicitacaoEdicaoPautaController::class, 'decidir'])
                ->name('pautas.solicitacoes.decidir');

            /*
            |--------------------------------------------------------------------------
            | Páginas Estatáticas
            |--------------------------------------------------------------------------
            */

            Route::inertia('horarios', 'horarios/index')
                ->name('horarios');

            Route::inertia('propinas/bloqueio', 'propinas/bloqueio')
                ->name('propinas.divida');

            /*
            |--------------------------------------------------------------------------
            | Histórico Académico
            |--------------------------------------------------------------------------
            */

            Route::get('historico/{aluno}/lancar', [PreencherHistoricoController::class, 'create'])
                ->name('preencher-historico.create');

            Route::post('historico/{aluno}/lancar', [PreencherHistoricoController::class, 'store'])
                ->name('preencher-historico.store');

            Route::post('historico/{aluno}/confirmar', [PreencherHistoricoController::class, 'confirmar'])
                ->name('preencher-historico.confirmar');

            /*
            |--------------------------------------------------------------------------
            | Documentos Escolares
            |--------------------------------------------------------------------------
            */

            Route::get('documentos', [DocumentosController::class, 'index'])
                ->name('documentos.index');

            Route::get('documentos/pesquisar-aluno', [DocumentosController::class, 'pesquisarAluno'])
                ->name('documentos.pesquisar-aluno');

            Route::match(['GET', 'POST'], 'documentos/exportar', [DocumentosController::class, 'exportar'])
                ->name('documentos.exportar');
        });
});
