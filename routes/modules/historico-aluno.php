<?php

use App\Http\Controllers\Tenant\PreencherHistoricoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Histórico de Alunos
|--------------------------------------------------------------------------
|
| Rotas para gerenciar o histórico académico de alunos.
| Permite o lançamento e confirmação de notas históricas.
|
*/

Route::get('historico/{aluno}/lancar', [PreencherHistoricoController::class, 'create'])
    ->name('preencher-historico.create');

Route::post('historico/{aluno}/lancar', [PreencherHistoricoController::class, 'store'])
    ->name('preencher-historico.store');

Route::post('historico/{aluno}/confirmar', [PreencherHistoricoController::class, 'confirmar'])
    ->name('preencher-historico.confirmar');
