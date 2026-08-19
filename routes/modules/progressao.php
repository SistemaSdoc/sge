<?php

use App\Http\Controllers\Tenant\ProgressaoController;
use Illuminate\Support\Facades\Route;

// Routa para gerar os certficados das instituições
Route::get(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/progressao/preview',
    [ProgressaoController::class, 'preview']
);

Route::post(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/progressao/preview',
    [ProgressaoController::class, 'store']
);
