<?php

use App\Http\Controllers\ClasseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::resource('classes', ClasseController::class);

require __DIR__.'/auth.php';

require __DIR__.'/settings.php';
