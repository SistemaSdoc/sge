<?php

use App\Http\Controllers\Central\TutelaController;
use Illuminate\Support\Facades\Route;

Route::prefix('central')->group(function (): void {
    Route::get('instituicoes', [TutelaController::class, 'instituicoes']);
    Route::get('instituicoes/{instituicaoId}/cursos', [TutelaController::class, 'cursosPorInstituicao']);
    Route::get('tutelas', [TutelaController::class, 'tutelas']);
});
