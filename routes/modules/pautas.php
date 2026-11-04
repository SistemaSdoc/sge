<?php

use App\Http\Controllers\Tenant\ExportarMiniPautaController;
use App\Http\Controllers\Tenant\ExportarPautaController;
use App\Http\Controllers\Tenant\SolicitacaoEdicaoPautaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pautas
|--------------------------------------------------------------------------
|
| Rotas para gerenciar e visualizar pautas de turmas e solicitações de edição.
|
*/

/**
 * Exporta a mini-pauta de uma disciplina de uma turma.
 */
<<<<<<< HEAD
Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/mini-pauta/export', [ExportarMiniPautaController::class, 'exportarDisciplina'])
=======
Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/mini-pauta/excel', [ExportarMiniPautaController::class, 'exportarDisciplina'])
    ->middleware('notes.two-factor:discipline')
>>>>>>> Autenticacao-2F
    ->name('exportar.mini-pauta.disciplina');

/**
 * Exporta a pauta de uma turma específica curso.
 */
<<<<<<< HEAD
Route::get('pautas/cursos/{cursoTutelado}/turmas/{turma}/pauta/export', [ExportarPautaController::class, 'exportarExcel'])
=======
Route::get('pautas/cursos/{cursoTutelado}/turmas/{turma}/exportar-pauta/excel', [ExportarPautaController::class, 'exportarExcel'])
    ->middleware('notes.two-factor:pauta')
>>>>>>> Autenticacao-2F
    ->name('exportar.pauta');

/**
 * Mostra a lista de solicitações de edição de pautas.
 */
Route::get('pautas/solicitacoes', [SolicitacaoEdicaoPautaController::class, 'index'])
    ->name('pautas.solicitacoes.index');

/**
 * Salva
 */
Route::post('pautas/solicitar-edicao', [SolicitacaoEdicaoPautaController::class, 'store'])
    ->name('pautas.solicitar-edicao');

Route::post('pautas/solicitacoes/{solicitacao}/decidir', [SolicitacaoEdicaoPautaController::class, 'decidir'])
    ->name('pautas.solicitacoes.decidir');
