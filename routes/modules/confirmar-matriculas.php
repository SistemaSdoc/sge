<?php

use App\Http\Controllers\ConfirmacaoMatriculaController;
use Illuminate\Support\Facades\Route;

/**
 * Mostra a lista de alunos por confirmar a sua matrícula.
 */
Route::get(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/confirmar-matriculas', [ConfirmacaoMatriculaController::class, 'index'])->name('confirmar-matriculas.index');

/**
 * confirma a matricula de um aluno.
 */
Route::post(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/confirmar-matriculas', [ConfirmacaoMatriculaController::class, 'store'])->name('confirmar-matriculas.store');
