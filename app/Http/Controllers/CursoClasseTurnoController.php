<?php

namespace App\Http\Controllers;

use App\Http\Resources\CursoClasseTurno\CursoClasseTurnoResource;
use App\Models\CursoClasse;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use Illuminate\Http\Request;

class CursoClasseTurnoController extends Controller
{
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load([
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
        ]);

        return CursoClasseTurnoResource::collection($cursoTutelado->cursoClasses);
    }

    public function update(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse)
    {
        abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

        $validated = $request->validate([
            'turnos' => ['required', 'array'],
            'turnos.*' => ['string', 'exists:turnos,id'],
        ]);

        // verifica se algum turno a remover tem turmas associadas
        $turnosActuais = $cursoClasse->turnos->pluck('turno_id');
        $turnosNovos = collect($validated['turnos']);
        $turnosARemover = $turnosActuais->diff($turnosNovos);

        if ($turnosARemover->isNotEmpty()) {
            $temTurmas = $cursoClasse->turnos()
                ->whereIn('turno_id', $turnosARemover)
                ->whereHas('turmas')
                ->exists();

            if ($temTurmas) {
                return response()->json([
                    'message' => 'Não é possível remover um turno que tem turmas associadas.',
                ], 422);
            }
        }

        $cursoClasse->turnos()->whereNotIn('turno_id', $validated['turnos'])->delete();

        foreach ($validated['turnos'] as $turnoId) {
            $cursoClasse->turnos()->firstOrCreate(['turno_id' => $turnoId]);
        }

        return response()->noContent();
    }
}
