<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use Inertia\Inertia;

class AnoLectivoController extends Controller
{
    public function index()
    {
        $anosLectivos = AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->paginate(15)
            ->through(fn (AnoLectivo $ano) => [
                'id' => $ano->id,
                'nome' => $ano->nome,
                'data_inicio' => $ano->data_inicio->format('Y-m-d H:i'),
                'data_fim' => $ano->data_fim->format('Y-m-d H:i'),
                'estado' => $ano->estado,
                'activo' => $ano->activo,
            ]);

        return Inertia::render('tenant/anos-lectivos/index', [
            'anosLectivos' => $anosLectivos,
        ]);
    }
}
