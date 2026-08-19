<?php

use App\Http\Controllers\Central\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Central\Auth\RegisteredController;
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

        Route::get('register', [RegisteredController::class, 'create'])
            ->middleware('guest')
            ->name('central.register');

        Route::post('register', [RegisteredController::class, 'store'])
            ->middleware('guest')
            ->name('central.register.store');

        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->middleware('guest')
            ->name('central.login');

        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('guest')
            ->middleware('throttle:login')
            ->name('central.login.store');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('central.logout');
    });
}

/**
 * Rotas de configurações de conta (alterar senha, atualizar perfil, etc.)
 * Estas rotas requerem autenticação e estão definidas em routes/settings.php
 * Middleware 'auth' já aplicado para garantir que apenas usuários autenticados possam acessar
 * As rotas específicas de configurações estão definidas em routes/settings.php
 */
require __DIR__.'/settings.php';
