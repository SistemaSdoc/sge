<?php

use App\Http\Controllers\Tenant\CertificadoController;
use Illuminate\Support\Facades\Route;

// Routa para gerar os certficados das instituições
Route::get(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/turmas/{turma}/alunos/{aluno}/certificado',
    [CertificadoController::class, 'gerar']
)->name('certificado.gerar')->withoutScopedBindings();

// Routa para gerar os certficados dos colegios que têm cursos tutelados
Route::get(
    '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/turmas/{turma}/alunos/{aluno}/certificado',
    [CertificadoController::class, 'gerarTutora']
)->name('certificado.gerarTutora')->withoutScopedBindings();
