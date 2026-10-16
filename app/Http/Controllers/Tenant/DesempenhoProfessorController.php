<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Professor;
use App\Services\Tenant\DesempenhoProfessorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DesempenhoProfessorController extends Controller
{
    public function __construct(private DesempenhoProfessorService $service)
    {
    }

    public function index(Request $request)
    {
        $professor = Professor::where('user_id', $request->user()->id)->firstOrFail();

        $anoLectivoId = $request->input('ano_lectivo_id') ?: null;
        $periodo = $request->input('periodo') ?: null;

        return Inertia::render('tenant/professor/desempenho', [
            ...$this->service->gerar($professor, $anoLectivoId, $periodo),
            'filtrosDisponiveis' => $this->service->filtrosDisponiveis(),
            'filtrosActivos' => [
                'ano_lectivo_id' => $anoLectivoId,
                'periodo' => $periodo,
            ],
            'rotaIndex' => route('tenant.dashboard.professor.desempenho'),
        ]);
    }
}