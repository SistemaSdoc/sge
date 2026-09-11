<?php

use App\Http\Controllers\SolicitacaoDocumentoController;
use Illuminate\Support\Facades\Route;

// Rotas para o módulo de solicitações de documentos
Route::get('solicitacoes-documentos', [SolicitacaoDocumentoController::class, 'index'])
    ->middleware('role:Aluno|SuperAdmin,tenant')
    ->name('solicitacoes-documentos.index');

Route::post('solicitacoes-documentos', [SolicitacaoDocumentoController::class, 'store'])
    ->middleware('role:Aluno|SuperAdmin,tenant')
    ->name('solicitacoes-documentos.store');

Route::get('solicitacoes-documentos/colegio', [SolicitacaoDocumentoController::class, 'colegioIndex'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.colegio.index');

Route::post('solicitacoes-documentos/{solicitacao}/enviar-para-tutela', [SolicitacaoDocumentoController::class, 'enviarParaTutela'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.enviar-para-tutela');

Route::get('solicitacoes-documentos/tutela', [SolicitacaoDocumentoController::class, 'tutelaIndex'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.tutela.index');

Route::post('solicitacoes-documentos/{solicitacao}/decidir', [SolicitacaoDocumentoController::class, 'processarDecisao'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.decidir');

Route::get('solicitacoes-documentos/tutela-dashboard', [SolicitacaoDocumentoController::class, 'tutelaDashboard'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.tutela.dashboard');

Route::get('solicitacoes-documentos/emissao', [SolicitacaoDocumentoController::class, 'emissaoIndex'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.emissao.index');

Route::post('solicitacoes-documentos/{solicitacao}/emitir', [SolicitacaoDocumentoController::class, 'emitir'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.emitir');

Route::post('solicitacoes-documentos/{solicitacao}/marcar-pago', [SolicitacaoDocumentoController::class, 'marcarComoPagoAction'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.marcar-como-pago');

Route::post('solicitacoes-documentos/{solicitacao}/marcar-levantado', [SolicitacaoDocumentoController::class, 'marcarComoLevantado'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.marcar-como-levantado');

Route::delete('solicitacoes-documentos/{solicitacao}', [SolicitacaoDocumentoController::class, 'destroy'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria,tenant')
    ->name('solicitacoes-documentos.destroy');

// Endpoint para obter histórico via AJAX (utilizado pelo painel lateral dinâmico)
Route::get('solicitacoes-documentos/history', [SolicitacaoDocumentoController::class, 'history'])
    ->middleware('role:SuperAdmin|Director|Subdirector|Secretaria|Aluno,tenant')
    ->name('solicitacoes-documentos.history');
