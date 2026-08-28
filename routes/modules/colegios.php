<?php

use App\Http\Controllers\Tenant\Colegios\BancaJuriPapController;
use App\Http\Controllers\Tenant\Colegios\ClasseTurnoTurmaController;
use App\Http\Controllers\Tenant\Colegios\ColegioController;
use App\Http\Controllers\Tenant\Colegios\CursoClasseController;
use App\Http\Controllers\Tenant\Colegios\CursoTuteladoController;
use App\Http\Controllers\Tenant\Colegios\ElementoGrupoPapController;
use App\Http\Controllers\Tenant\Colegios\GrupoPapAprovacaoController;
use App\Http\Controllers\Tenant\Colegios\GrupoPapController;
use App\Http\Controllers\Tenant\Colegios\NotaDisciplinaController;
use App\Http\Controllers\Tenant\Colegios\TrabalhoPapController;
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
                    ->name('colegios.cursos.classes.turnos.turmas.pap.elementos.actualizarNota');

                // Show do grupo PAP
                Route::get('/', [GrupoPapController::class, 'show'])
                    ->name('colegios.cursos.classes.turnos.turmas.pap.show');

                // Aprovação — dentro do aninhamento completo
                Route::post('/aprovar', [GrupoPapAprovacaoController::class, 'aprovar'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar');

                Route::post('/reprovar', [GrupoPapAprovacaoController::class, 'reprovar'])
                    ->name('colegio.grupo-pap-aprovacao.reprovar');

                Route::post('/solicitar-melhoria', [GrupoPapAprovacaoController::class, 'solicitarMelhoria'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria');

                Route::post('/aprovar-tutor', [GrupoPapAprovacaoController::class, 'aprovarTutor'])
                    ->name('colegio.grupo-pap-aprovacao.aprovar-tutor');

                Route::post('/solicitar-melhoria-tutor', [GrupoPapAprovacaoController::class, 'solicitarMelhoriaComoTutor'])
                    ->name('colegio.grupo-pap-aprovacao.solicitar-melhoria-tutor');

                Route::post('/reenviar', [GrupoPapAprovacaoController::class, 'reenviar'])
                    ->name('colegio.grupo-pap-aprovacao.reenviar');

                // Banca
                Route::resource('banca', BancaJuriPapController::class)
                    ->parameters(['banca' => 'bancaJuriPap'])
                    ->only(['create', 'store', 'edit', 'update', 'destroy']);

                Route::post('/reenviar', [GrupoPapAprovacaoController::class, 'reenviar'])
                    ->name('colegio.grupo-pap-aprovacao.reenviar');

                // Banca
                Route::resource('banca', BancaJuriPapController::class)
                    ->parameters(['banca' => 'bancaJuriPap'])
                    ->only(['create', 'store', 'edit', 'update', 'destroy']);

                    // Trabalho PAP
                Route::prefix('trabalho')
                    ->name('colegios.pap.trabalho.')
                    ->group(function () {
                        Route::post('/submeter', [TrabalhoPapController::class, 'submeter'])
                            ->name('submeter');
                        Route::post('/tutor/aprovar', [TrabalhoPapController::class, 'aprovarComoTutor'])
                            ->name('tutor.aprovar');
                        Route::post('/tutor/correcao', [ControllersTrabalhoPapController::class, 'solicitarCorrecaoComoTutor'])
                            ->name('tutor.correcao');
                        Route::post('/coordenacao/aprovar', [TrabalhoPapController::class, 'aprovarComoCoordenacao'])
                            ->name('coordenacao.aprovar');
                        Route::post('/coordenacao/correcao', [TrabalhoPapController::class, 'solicitarCorrecaoComoCoordenacao'])
                            ->name('coordenacao.correcao');
                        Route::get('/versao/{numeroVersao}/download', [TrabalhoPapController::class, 'download'])
                            ->name('versao.download')
                            ->whereNumber('numeroVersao');
                        Route::get('/versao/{numeroVersao}/visualizar', [TrabalhoPapController::class, 'visualizar'])
                            ->name('versao.visualizar')
                            ->whereNumber('numeroVersao');
                        Route::get('/correcao/{feedbackId}/download', [TrabalhoPapController::class, 'downloadCorrecao'])
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

        Route::get('cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/notas', [NotaDisciplinaController::class, 'index'])
            ->name('colegios.cursos.classes.turnos.turmas.disciplinas.notas.index');

        // Melhorias e edição — sem grupoPap no prefixo
        Route::get('grupo-pap-aprovacao/melhorias', [GrupoPapAprovacaoController::class, 'melhorias'])
            ->name('colegio.grupo-pap-aprovacao.melhorias');

        Route::get('grupo-pap-aprovacao/{grupoPap}/editar', [GrupoPapAprovacaoController::class, 'editar'])
            ->name('colegio.grupo-pap-aprovacao.editar');

        Route::put('grupo-pap-aprovacao/{grupoPap}', [GrupoPapAprovacaoController::class, 'atualizar'])
            ->name('colegio.grupo-pap-aprovacao.atualizar');
    });
});
