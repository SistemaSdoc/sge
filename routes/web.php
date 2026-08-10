<?php

use App\Http\Controllers\CertificadoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome/index')->name('home');

/**
 * Dashboard routes para Staff (Master, Director, Coordenador, Secretaria, Professor)
 * Todas estas rotas requerem autenticação e role de staff
 * Middleware 'auth' e 'role:Master,Director,Coordenador,Secretaria,Professor' já aplicado no grupo principal
 * Prefixo 'dashboard' e nome 'dashboard.' já aplicado no grupo principal
 * As rotas específicas do dashboard estão definidas em routes
 */
Route::middleware(['auth', 'verified', 'role:SuperAdmin|Director|Subdirector|Secretaria|Professor|Aluno'])
    ->prefix('dashboard')
    ->group(base_path('routes/dashboard.php'));

/**
 * Rotas de autenticação (login, logout, registro, etc.) e configurações de conta
 * Estas rotas são geradas pelo Laravel Breeze e estão definidas em routes/auth.php
 * Middleware 'auth' já aplicado para rotas que requerem autenticação
 * As rotas específicas de configurações estão definidas em routes/settings.php
 */
require __DIR__.'/auth.php';

/**
 * Rotas de configurações de conta (alterar senha, atualizar perfil, etc.)
 * Estas rotas requerem autenticação e estão definidas em routes/settings.php
 * Middleware 'auth' já aplicado para garantir que apenas usuários autenticados possam acessar
 * As rotas específicas de configurações estão definidas em routes/settings.php
 */
require __DIR__.'/settings.php';

Route::get(
    '/certificados/{aluno}/verificar',
    [CertificadoController::class, 'show']
)->name('certificados.verificar');

Route::inertia('/candidatura', 'teste');

// Proxy route to consult BI externally (avoids CORS issues)
Route::get('/bi/consultar/{bi}', [\App\Http\Controllers\BiController::class, 'consult']);
