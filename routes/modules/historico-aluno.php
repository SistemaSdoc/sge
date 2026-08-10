<?php

use App\Http\Controllers\ConfirmacaoMatriculaController;
use App\Http\Controllers\PreencherHistoricoController;
use Illuminate\Support\Facades\Route;

/**
 * Mostra a página de preenchimento do histórico de um aluno.
 */

Route::get('/historico/{aluno}', [PreencherHistoricoController::class, 'show'])
    ->name('historico.show');

Route::get('/historico/turnos', [PreencherHistoricoController::class, 'getTurnos'])
    ->name('historico.turnos');

Route::get('/historico/turmas', [PreencherHistoricoController::class, 'getTurmas'])
    ->name('historico.turmas');

Route::post('/historico/{aluno}/confirmar', [PreencherHistoricoController::class, 'confirmar'])
    ->name('historico.confirmar');

Route::get('/historico/{aluno}/{turmaAluno}/create', [PreencherHistoricoController::class, 'create'])
    ->name('historico.create');
