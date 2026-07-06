<?php

use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordConfirmationGoogleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('guest')
        ->name('google.redirect');

    Route::get('google/callback', [GoogleAuthController::class, 'callback'])
        ->name('google.callback');

    Route::get('facebook/redirect', [FacebookAuthController::class, 'redirect'])
        ->middleware('guest')
        ->name('facebook.redirect');

    Route::get('facebook/callback', [FacebookAuthController::class, 'callback'])
        ->name('facebook.callback');
});

Route::prefix('password-confirmation')->name('password-confirmation-google.')
    ->middleware(['auth'])->group(function () {
        Route::get('google/redirect', [PasswordConfirmationGoogleController::class, 'redirect'])
            ->name('redirect');

        Route::get('google/callback', [PasswordConfirmationGoogleController::class, 'callback'])
            ->name('callback');
    });
