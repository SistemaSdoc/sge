<?php

use App\Http\Controllers\Central\Settings\ProfileController;
use App\Http\Controllers\Central\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::redirect('settings', '/settings/profile')->name('settings');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'tenant/settings/appearance')
        ->name('appearance.edit');
});
