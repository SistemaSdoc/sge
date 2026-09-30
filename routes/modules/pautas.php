<?php

use App\Http\Controllers\Tenant\CursoTuteladoController;
use App\Http\Controllers\Tenant\ExportarMiniPautaController;
use App\Http\Controllers\Tenant\ExportarPautaController;
use App\Http\Controllers\Tenant\PautaController;
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

Route::get('pautas', [PautaController::class, 'index'])
    ->name('pautas.index');

/**
 * Mostra a pauta de uma turma.
 */
Route::get('pauta/{turma}', [PautaController::class, 'pauta'])
    ->name('pautas.pauta');

/**
 * Exporta a mini-pauta de uma disciplina de uma turma.
 */
Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/disciplinas/{classeTurnoDisciplina}/mini-pauta/excel', [ExportarMiniPautaController::class, 'exportarDisciplina'])
    ->name('exportar.mini-pauta.disciplina');

/**
 * Exporta a pauta completa de uma turma.
 */
Route::get('pautas/cursos/{cursoTutelado}/turmas/{turma}/exportar-pauta/excel', [ExportarPautaController::class, 'exportarExcel'])
    ->name('exportar.pauta');

Route::post('pautas/solicitar-edicao', [SolicitacaoEdicaoPautaController::class, 'store'])
    ->name('pautas.solicitar-edicao');

Route::get('instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/turmas/{turma}/pauta', [CursoTuteladoController::class, 'pauta'])
    ->name('pauta');

Route::get('pautas/solicitacoes', [SolicitacaoEdicaoPautaController::class, 'index'])
    ->name('pautas.solicitacoes.index');

Route::post('pautas/solicitacoes/{solicitacao}/decidir', [SolicitacaoEdicaoPautaController::class, 'decidir'])
    ->name('pautas.solicitacoes.decidir');
