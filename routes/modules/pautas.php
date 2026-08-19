<?php

use App\Http\Controllers\CursoTuteladoController;
use App\Http\Controllers\ExportarMiniPautaController;
use App\Http\Controllers\ExportarPautaController;
use App\Http\Controllers\PautaController;
use App\Http\Controllers\SolicitacaoEdicaoPautaController;
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
 * Mostra a lista de cursos tutelados da instituição do user logado
 */
Route::get('pautas/cursos', [PautaController::class, 'indexCursos'])
    ->name('pautas.cursos');

/**
 * Mostra a lista de turmas de um curso tutelado
 */
Route::get('pautas/cursos/{cursoTutelado}/turmas', [PautaController::class, 'indexTurmas'])
    ->name('pautas.cursos.turmas');

/**
 * Mostra a pauta de uma turma de um curso tutelado
 */
Route::get('pautas/cursos/{cursoTutelado}/turmas/{turma}/pauta', [PautaController::class, 'pauta'])
    ->name('pautas.cursos.turmas.pauta');

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
