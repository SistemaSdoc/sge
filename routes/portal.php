<?php

use Illuminate\Support\Facades\Route;

// Portal routes para Candidato e Aluno
// Todas estas rotas requerem autenticação e role 'candidato' ou 'aluno'
// Middleware 'auth' e 'role:candidato,aluno' já aplicado no grupo principal

Route::get('/', fn () => inertia('portal/index'))->name('portal.index');
Route::get('perfil', fn () => inertia('portal/perfil'))->name('portal.perfil');
Route::get('avisos', fn () => inertia('portal/avisos'))->name('portal.avisos');
Route::get('horario', fn () => inertia('portal/horario'))->name('portal.horario');
Route::get('notas', fn () => inertia('portal/notas'))->name('portal.notas');
