<?php

use App\Http\Controllers\AccessManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:permissoes.gerir'])->group(function () {
    Route::get('/access-management', [AccessManagementController::class, 'index'])
        ->name('access-management.index');

    Route::post('/access-management/{user}', [AccessManagementController::class, 'store'])
        ->name('access-management.store');
});
