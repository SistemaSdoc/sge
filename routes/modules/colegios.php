<?php

use App\Http\Controllers\Colegios\ClasseTurnoTurmaController;
use App\Http\Controllers\Colegios\ColegioController;
use App\Http\Controllers\Colegios\CursoClasseController;
use App\Http\Controllers\Colegios\CursoTuteladoController;
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
});