<?php

use App\Http\Controllers\BiController;
use App\Http\Controllers\CertificadoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Aqui você pode registrar as rotas centrais para a aplicação.
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::inertia('/', 'welcome/index')->name('home');
    });
}

/**
 * Rotas de configurações de conta (alterar senha, atualizar perfil, etc.)
 * Estas rotas requerem autenticação e estão definidas em routes/settings.php
 * Middleware 'auth' já aplicado para garantir que apenas usuários autenticados possam acessar
 * As rotas específicas de configurações estão definidas em routes/settings.php
 */
require __DIR__.'/settings.php';



