<?php

use App\Http\Controllers\Colegios\BancaJuriPapController;
use App\Http\Controllers\Colegios\ClasseTurnoTurmaController;
use App\Http\Controllers\Colegios\ColegioController;
use App\Http\Controllers\Colegios\CursoClasseController;
use App\Http\Controllers\Colegios\CursoTuteladoController;
use App\Http\Controllers\Colegios\GrupoPapAprovacaoController;
use App\Http\Controllers\Colegios\GrupoPapController;
use App\Http\Controllers\Colegios\NotaDisciplinaController;
use Illuminate\Support\Facades\Route;

Route::prefix('colegios')->group(function () {
    Route::get('/', [ColegioController::class, 'index'])
        ->name('colegios.index');

    Route::get('{colegio}', [ColegioController::class, 'show'])
        ->name('colegios.show');

    Route::get('{colegio}/cursos/{cursoTutelado}', [CursoTuteladoController::class, 'show'])
        ->name('colegios.cursos.show');

    Route::get('{colegio}/cursos/{cursoTutelado}/classes/{cursoClasse}', [CursoClasseController::class, 'show'])
        ->name('colegios.cursos.classes.show');

    Route::get('{colegio}/cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}', [ClasseTurnoTurmaController::class, 'show'])
        ->name('colegios.cursos.classes.turnos.turmas.show');

    Route::get('{colegio}/cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/notas', [NotaDisciplinaController::class, 'index'])
        ->name('colegios.cursos.classes.turnos.turmas.disciplinas.notas.index');

    Route::get('{colegio}/cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}', [GrupoPapController::class, 'show'])
        ->name('colegios.cursos.classes.turnos.turmas.pap.show');

    Route::prefix('{colegio}/cursos/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/pap/{grupoPap}')->group(function () {
        Route::resource('banca', BancaJuriPapController::class)
            ->parameters(['banca' => 'bancaJuriPap'])
            ->only(['create', 'store', 'edit', 'update', 'destroy']);
    });

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


    Route::get('/grupo-pap-aprovacao/melhorias', [GrupoPapAprovacaoController::class, 'melhorias'])
    ->name(
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

    Route::post(
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

});