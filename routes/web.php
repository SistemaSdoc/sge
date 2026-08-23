<?php

use App\Http\Controllers\Central\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Central\Auth\RegisteredController;
use App\Http\Controllers\Central\DashboardController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\UserController;
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
        /*
        |--------------------------------------------------------------------------
        | Welcome Page
        |--------------------------------------------------------------------------
        */
        Route::inertia('/', 'central/welcome/index')->name('home');

        /*
        |--------------------------------------------------------------------------
        | Rotas de Autenticação Central
        |--------------------------------------------------------------------------
        */
        Route::get('register', [RegisteredController::class, 'create'])
            ->middleware('guest:web')
            ->name('central.register');

        Route::post('register', [RegisteredController::class, 'store'])
            ->middleware('guest:web')
            ->name('central.register.store');

        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->middleware('guest:web')
            ->name('central.login');

        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('guest:web')
            ->middleware('throttle:login')
            ->name('central.login.store');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth:web')
            ->name('central.logout');

        /*
        |--------------------------------------------------------------------------
        | Rotas Internas do central (Dashboard Routes)
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('auth:web')
            ->name('central.dashboard');

        Route::middleware([
            'auth:web',
            'verified',
            'role:SuperAdmin',
        ])
            ->prefix('dashboard')
            ->name('central.dashboard.')
            ->group(function () {

                Route::resource('tenants', TenantController::class);

                Route::post('tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])
                    ->name('tenants.toggle-status');

                Route::get('tenants/{tenant}/status-stream', [TenantController::class, 'statusStream'])
                    ->name('tenants.status-stream');

                Route::resource('users', UserController::class);
            });
    });
}
