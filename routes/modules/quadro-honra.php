<?php

use App\Http\Controllers\Tenant\QuadroHonraController;
use Illuminate\Support\Facades\Route;

// As rotas são incluídas no grupo tenant autenticado e herdam o prefixo dashboard.
Route::prefix('quadro-honra')->name('quadro-honra.')->group(function (): void {
    Route::get('/', [QuadroHonraController::class, 'index'])
        ->name('index');

    Route::post('/previsualizar', [QuadroHonraController::class, 'previsualizar'])
        ->name('previsualizar');

    Route::patch('/criterios', [QuadroHonraController::class, 'atualizarCriterios'])
        ->name('criterios.update');
});
