<?php

use App\Http\Controllers\ConfirmacaoMatriculaController;
use Illuminate\Support\Facades\Route;

/**
 * Mostra a lista de alunos por confirmar a sua matrícula (Após transitar de classe).
 */
Route::get('confirmar-matriculas', [ConfirmacaoMatriculaController::class, 'index'])
    ->name('confirmar-matriculas.index');

/**
 * confirma a matricula de um aluno.
 */
Route::post('confirmar-matriculas/aluno/{aluno}', [ConfirmacaoMatriculaController::class, 'store'])
    ->name('confirmar-matriculas.store');
