<?php

use App\Http\Controllers\Tenant\Auth\FacebookAuthController;
use App\Http\Controllers\Tenant\Auth\GoogleAuthController;
use App\Http\Controllers\Tenant\Auth\PasswordConfirmationGoogleController;
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

/*
//por terminar...
Route::prefix('password-confirmation')->name('password-confirmation-google.')
    ->middleware(['auth'])->group(function () {
        Route::get('google/redirect', [PasswordConfirmationGoogleController::class, 'redirect'])
            ->name('redirect');

        Route::get('google/callback', [PasswordConfirmationGoogleController::class, 'callback'])
            ->name('callback');
    });*/

/*Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])
->middleware('guest:tenant')
->name('tenant.google.redirect');

    Route::get('google/callback', [GoogleAuthController::class, 'callback'])
->middleware('guest:tenant')
->name('tenant.google.callback');

    Route::get('facebook/redirect', [FacebookAuthController::class, 'redirect'])
->middleware('guest:tenant')
->name('tenant.facebook.redirect');

    Route::get('facebook/callback', [FacebookAuthController::class, 'callback'])
->middleware('guest:tenant')
->name('tenant.facebook.callback');*/
