<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\DashboardDirectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardDirectorController extends Controller
{
    public function __construct(private DashboardDirectorService $service) {}

    /**
     * Obter métricas gerais do dashboard
     */
    public function metricas(): JsonResponse
    {
        $instituicaoId = $this->instituicaoId();
        
        $metricas = $this->service->obterMetricas($instituicaoId);

        return response()->json($metricas);
    }

    /**
     * Obter ações pendentes
     */
    public function accoesPendentes(Request $request): JsonResponse
    {
        $instituicaoId = $this->instituicaoId();
        $acoes = $this->service->obterAccoesPendentes($instituicaoId);

        return response()->json($acoes);
    }

    /**
     * Obter próximos eventos
     */
    public function avisos(): JsonResponse
    {
        $instituicaoId = $this->instituicaoId();
        $eventos = $this->service->obterEventos($instituicaoId);

        return response()->json($eventos);
    }

    /**
     * Obter ID da instituição do usuário autenticado
     */
    protected function instituicaoId(): ?string
    {
        return Auth::user()?->instituicaoFiltro();
    }
}
