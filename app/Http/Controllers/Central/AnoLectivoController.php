<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\AnoLectivoRequest;
use App\Models\Central\AnoLectivo;
use App\Services\Central\AnoLectivoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnoLectivoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('central/anos-lectivos/index', [
            'anosLectivos' => AnoLectivo::query()
                ->withTrashed()
                ->orderByDesc('data_inicio')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('central/anos-lectivos/create');
    }

    public function store(AnoLectivoRequest $request, AnoLectivoService $service): RedirectResponse
    {
        $service->criar((int) $request->validated('ano_inicio'));

        return to_route('central.dashboard.anos-lectivos.index')
            ->with('success', 'Ano lectivo criado com sucesso.');
    }

    public function archive(AnoLectivo $anoLectivo, AnoLectivoService $service): RedirectResponse
    {
        $service->arquivar($anoLectivo);

        return to_route('central.dashboard.anos-lectivos.index')
            ->with('success', 'Ano lectivo arquivado com sucesso.');
    }

    public function restore(AnoLectivo $anoLectivo, AnoLectivoService $service): RedirectResponse
    {
        $service->restaurar($anoLectivo);

        return to_route('central.dashboard.anos-lectivos.index')
            ->with('success', 'Ano lectivo restaurado com sucesso.');
    }
}
