<?php

use App\Http\Controllers\ConfirmacaoMatriculaController;
use App\Http\Controllers\PreencherHistoricoController;
use Illuminate\Support\Facades\Route;

/**
 * Mostra a página de preenchimento do histórico de um aluno.
 */
Route::get(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/preencher-historico', [ConfirmacaoMatriculaController::class, 'index'])->name('confirmar-matriculas.index')->name('preencher-historico.create');

/**
 * Salva o histórico de um aluno.
 */
Route::post(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/preencher-historico', [ConfirmacaoMatriculaController::class, 'store'])->name('preencher-historico.store');

Route::get('/historico/{aluno}', [PreencherHistoricoController::class, 'show'])
    ->name('historico.show');

Route::get('/historico/turnos', [PreencherHistoricoController::class, 'getTurnos'])
    ->name('historico.turnos');

Route::get('/historico/turmas', [PreencherHistoricoController::class, 'getTurmas'])
    ->name('historico.turmas');

Route::post('/historico/{aluno}/confirmar', [PreencherHistoricoController::class, 'confirmar'])
    ->name('historico.confirmar');
