<?php

use App\Http\Controllers\Tenant\Settings\ProfileController;
use App\Http\Controllers\Tenant\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:tenant'])->group(function () {
    Route::get('settings', fn () => to_route('tenant.dashboard.profile.edit'))
        ->name('settings')
        ->withoutMiddleware('verified');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit')
        ->withoutMiddleware('verified');

    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->name('profile.update')
        ->withoutMiddleware('verified');
});

Route::middleware(['auth:tenant', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'tenant/settings/appearance')
        ->name('appearance.edit')
        ->withoutMiddleware('verified');
});
