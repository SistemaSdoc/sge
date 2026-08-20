<?php

use App\Http\Controllers\Central\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Central\Auth\RegisteredController;
use App\Http\Controllers\Central\DashboardController;
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
        Route::inertia('/', 'tenant/welcome/index')->name('home');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('auth:web')
            ->name('central.dashboard');

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
    });
}
