<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboards\Aluno\ProximasAulasResource;
use App\Services\Dashboards\DashboardAlunoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardAlunoController extends Controller
{
    public function __construct(private DashboardAlunoService $service) {}

    /**
     * Obter as aulas do dia para o aluno autenticado
     */
    public function proximasAulas()
    {
        $aluno = Auth::user()->aluno;

        $aulas = $this->service->obterProximasAulas($aluno, 2, 6);

        return ProximasAulasResource::collection($aulas);
    }

    /**
     * Obter resumo acadêmico (notas, médias, faltas) para o aluno autenticado
     */
    public function resumoAcademico(): JsonResponse
    {
        $aluno = Auth::user()->aluno;

        $resumo = $this->service->obterResumoAcademico($aluno);

        return response()->json(['data' => $resumo]);
    }

    /**
     * Obter avisos/notificações para o aluno autenticado
     */
    public function avisos(): JsonResponse
    {
        $aluno = Auth::user()->aluno;

        $avisos = $this->service->obterAvisos($aluno);

        return response()->json([
            'data' => $avisos,
        ]);
    }
}
