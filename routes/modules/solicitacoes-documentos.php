<?php

use App\Http\Controllers\SolicitacaoDocumentoController;
use Illuminate\Support\Facades\Route;

// Rotas para o módulo de solicitações de documentos
Route::get('solicitacoes-documentos', [SolicitacaoDocumentoController::class, 'index'])
    ->name('solicitacoes-documentos.index');

Route::post('solicitacoes-documentos', [SolicitacaoDocumentoController::class, 'store'])
    ->name('solicitacoes-documentos.store');

Route::get('solicitacoes-documentos/colegio', [SolicitacaoDocumentoController::class, 'colegioIndex'])
    ->name('solicitacoes-documentos.colegio.index');

Route::post('solicitacoes-documentos/{solicitacao}/enviar-para-tutela', [SolicitacaoDocumentoController::class, 'enviarParaTutela'])
    ->name('solicitacoes-documentos.enviar-para-tutela');

Route::get('solicitacoes-documentos/tutela', [SolicitacaoDocumentoController::class, 'tutelaIndex'])
    ->name('solicitacoes-documentos.tutela.index');

Route::post('solicitacoes-documentos/{solicitacao}/decidir', [SolicitacaoDocumentoController::class, 'processarDecisao'])
    ->name('solicitacoes-documentos.decidir');

Route::get('solicitacoes-documentos/tutela-dashboard', [SolicitacaoDocumentoController::class, 'tutelaDashboard'])
    ->name('solicitacoes-documentos.tutela.dashboard');

Route::get('solicitacoes-documentos/emissao', [SolicitacaoDocumentoController::class, 'emissaoIndex'])
    ->name('solicitacoes-documentos.emissao.index');

Route::post('solicitacoes-documentos/{solicitacao}/emitir', [SolicitacaoDocumentoController::class, 'emitir'])
    ->name('solicitacoes-documentos.emitir');

Route::post('solicitacoes-documentos/{solicitacao}/marcar-pago', [SolicitacaoDocumentoController::class, 'marcarComoPagoAction'])
    ->name('solicitacoes-documentos.marcar-como-pago');

Route::post('solicitacoes-documentos/{solicitacao}/marcar-levantado', [SolicitacaoDocumentoController::class, 'marcarComoLevantado'])
    ->name('solicitacoes-documentos.marcar-como-levantado');

Route::delete('solicitacoes-documentos/{solicitacao}', [SolicitacaoDocumentoController::class, 'destroy'])
    ->name('solicitacoes-documentos.destroy');