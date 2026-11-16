<?php

use App\Http\Controllers\Tenant\Colegios\BancaJuriPapController;
use App\Http\Controllers\Tenant\Colegios\ClasseTurnoTurmaController;
use App\Http\Controllers\Tenant\Colegios\ColegioController;
use App\Http\Controllers\Tenant\Colegios\CursoClasseController;
use App\Http\Controllers\Tenant\Colegios\CursoTuteladoController;
use App\Http\Controllers\Tenant\Colegios\ElementoGrupoPapController;
use App\Http\Controllers\Tenant\Colegios\ExportarListaAlunosTurmaController;
use App\Http\Controllers\Tenant\Colegios\NotaDisciplinaController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao\AprovarTemaPapController as AprovarTemaPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao\ReenviarTemaPapController as ReenviarTemaPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao\ReprovarTemaPapController as ReprovarTemaPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao\SolicitarMelhoriaTemaPapController as SolicitarMelhoriaTemaPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Tutor\AprovarTemaPapController as AprovarTemaPapTutorController;
use App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Tutor\SolicitarMelhoriaTemaPapController as SolicitarMelhoriaTemaPapTutorController;
use App\Http\Controllers\Tenant\Colegios\Pap\GrupoPapController;
use App\Http\Controllers\Tenant\Colegios\Pap\TemaPapController as ColegioTemaPapController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Coordenacao\AprovarTrabalhoPapController as AprovarTrabalhoPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Coordenacao\SolicitarCorrecaoTrabalhoPapController as SolicitarCorrecaoTrabalhoPapCoordenacaoController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\SubmeterTrabalhoPapController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\TrabalhoPapController as TrabalhoPapLeituraController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Tutor\AprovarTrabalhoPapController as AprovarTrabalhoPapTutorController;
use App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Tutor\SolicitarCorrecaoTrabalhoPapController as SolicitarCorrecaoTrabalhoPapTutorController;
use Illuminate\Support\Facades\Route;

// colegios.php
Route::prefix('colegios')->group(function () {

    Route::get('/', [ColegioController::class, 'index'])
        ->name('colegios.index');

    Route::prefix('{colegio}')->group(function () {

        Route::get('/', [ColegioController::class, 'show'])
            ->name('colegios.show');

        Route::prefix('cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}')
            ->group(function () {

                Route::put('/elementos/{elementoGrupoPap}/nota', [ElementoGrupoPapController::class, 'actualizarNota'])
                    ->middleware('cross.tenant')
                    ->name('colegios.cursos.classes.turnos.turmas.pap.elementos.actualizarNota');

                // Show do grupo PAP
                Route::get('/', [GrupoPapController::class, 'show'])
                    ->name('colegios.cursos.classes.turnos.turmas.pap.show');

                Route::put('/data-defesa', [GrupoPapController::class, 'definirData'])
                    ->name('colegios.cursos.classes.turnos.turmas.pap.definir-data');

                // Aprovação — dentro do aninhamento completo
                Route::post('/aprovar', [AprovarTemaPapCoordenacaoController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar');

                Route::post('/reprovar', [ReprovarTemaPapCoordenacaoController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.reprovar');

                Route::post('/solicitar-melhoria', [SolicitarMelhoriaTemaPapCoordenacaoController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria');

                Route::get('/solicitar-melhoria-coordenacao', [SolicitarMelhoriaTemaPapCoordenacaoController::class, 'create'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria-coordenacao.form');

                Route::get('/aprovar-coordenacao', [AprovarTemaPapCoordenacaoController::class, 'create'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar-coordenacao.form');

                Route::get('/reprovar-coordenacao', [ReprovarTemaPapCoordenacaoController::class, 'create'])
                    ->name('colegio.grupo-pap-aprovacao.reprovar-coordenacao.form');

                Route::get('/aprovar-tutor', [AprovarTemaPapTutorController::class, 'create'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar-tutor.form');

                Route::post('/aprovar-tutor', [AprovarTemaPapTutorController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar-tutor');

                Route::get('/solicitar-melhoria-tutor', [SolicitarMelhoriaTemaPapTutorController::class, 'create'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria-tutor.form');

                Route::post('/solicitar-melhoria-tutor', [SolicitarMelhoriaTemaPapTutorController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria-tutor');

                Route::post('/reenviar', [ReenviarTemaPapCoordenacaoController::class, 'store'])
                    ->name('colegio.grupo-pap-aprovacao.reenviar');

                // Banca
                Route::resource('banca', BancaJuriPapController::class)
                    ->parameters(['banca' => 'bancaJuriPap'])
                    ->middleware('cross.tenant')
                    ->only(['create', 'store', 'edit', 'update', 'destroy']);

                // Trabalho PAP
                Route::prefix('trabalho')
                    ->name('colegios.pap.trabalho.')
                    ->group(function () {
                        Route::post('/submeter', [SubmeterTrabalhoPapController::class, 'store'])
                            ->name('submeter');
                        Route::post('/tutor/aprovar', [AprovarTrabalhoPapTutorController::class, 'store'])
                            ->name('tutor.aprovar');
                        Route::post('/tutor/correcao', [SolicitarCorrecaoTrabalhoPapTutorController::class, 'store'])
                            ->name('tutor.correcao');
                        Route::post('/coordenacao/aprovar', [AprovarTrabalhoPapCoordenacaoController::class, 'store'])
                            ->name('coordenacao.aprovar');
                        Route::post('/coordenacao/correcao', [SolicitarCorrecaoTrabalhoPapCoordenacaoController::class, 'store'])
                            ->name('coordenacao.correcao');
                        Route::get('/versao/{numeroVersao}/download', [TrabalhoPapLeituraController::class, 'download'])
                            ->name('versao.download')
                            ->whereNumber('numeroVersao');
                        Route::get('/versao/{numeroVersao}/visualizar', [TrabalhoPapLeituraController::class, 'visualizar'])
                            ->name('versao.visualizar')
                            ->whereNumber('numeroVersao');
                        Route::get('/correcao/{feedbackId}/download', [TrabalhoPapLeituraController::class, 'downloadCorrecao'])
                            ->name('correcao.download');
                    });
            });

        // Outras rotas sem grupoPap
        Route::get('cursos/{cursoTutelado}', [CursoTuteladoController::class, 'show'])
            ->name('colegios.cursos.show');

        Route::get('cursos/{cursoTutelado}/classes/{cursoClasse}', [CursoClasseController::class, 'show'])
            ->name('colegios.cursos.classes.show');

        Route::get('cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}', [ClasseTurnoTurmaController::class, 'show'])
            ->name('colegios.cursos.classes.turnos.turmas.show');

        Route::get('cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/alunos/exportar', [ExportarListaAlunosTurmaController::class, 'exportar'])
            ->name('colegios.cursos.classes.turnos.turmas.alunos.exportar');

        Route::get('cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/notas', [NotaDisciplinaController::class, 'index'])
            ->middleware('notes.two-factor:discipline')
            ->name('colegios.cursos.classes.turnos.turmas.disciplinas.notas.index');

        // Melhorias e edição — sem grupoPap no prefixo
        Route::get('grupo-pap-aprovacao/{grupoPap}/editar', [ColegioTemaPapController::class, 'edit'])
            ->name('colegio.grupo-pap-aprovacao.editar');

        Route::put('grupo-pap-aprovacao/{grupoPap}', [ColegioTemaPapController::class, 'update'])
            ->name('colegio.grupo-pap-aprovacao.atualizar');
    });
});
