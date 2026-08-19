<?php

namespace App\Http\Controllers\Tenant\Dashboards;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboards\Professor\ProximasAulasResource;
use App\Services\Dashboards\DashboardProfessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardProfessorController extends Controller
{
    public function __construct(private DashboardProfessorService $service) {}

    /**
     * Obter as próximas aulas do professor nas turmas em que leciona
     */
    public function proximasAulas()
    {
        $professor = Auth::user()->professor;

        $aulas = $this->service->obterProximasAulas($professor, 2, 6);

        return ProximasAulasResource::collection($aulas);
    }

    /**
     * Obter resumo acadêmico do professor (disciplinas, turmas)
     */
    public function resumoAcademico(): JsonResponse
    {
        $professor = Auth::user()->professor;

        $resumo = $this->service->obterResumoAcademico($professor);

        return response()->json(['data' => $resumo]);
    }

    /**
     * Obter avisos/notificações para o professor
     */
    public function avisos()
    {
        $professor = Auth::user()->professor;

        $avisos = $this->service->obterAvisos($professor);

        return response()->json([
            'data' => $avisos,
        ]);
    }
}
