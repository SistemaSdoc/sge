<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Turma;
use App\Services\Tenant\RelatorioService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RelatorioController extends Controller
{
    public function __construct(protected RelatorioService $service)
    {
    }

    public function index(Request $request)
    {
        $filtros = $request->only([
            'tipo', 'pesquisa', 'ano_lectivo_id', 'turma_id',
            'situacao', 'estado', 'classe_id',
            'data_inicio', 'data_fim', 'por_pagina',
        ]);

        $dados = $this->service->gerar($filtros);

       return Inertia::render('tenant/relatorio/index', [
    'turmas' => Turma::orderBy('nome')->get(['id', 'nome']),
    'classes' => Classe::orderBy('nome')->get(['id', 'nome']),
    'tipo' => $filtros['tipo'] ?? 'geral',
    'filtros' => $filtros,
    'anosLectivos' => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
    ...$dados,
]);
    }
}