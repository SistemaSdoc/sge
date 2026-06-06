<?php

namespace App\Http\Controllers;

use App\Http\Resources\CursoClasseTurno\CursoClasseTurnoResource;
use App\Models\CursoClasse;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoClasseTurnoController extends Controller
{
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load([
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
        ]);

        // If the client expects JSON for a non-Inertia API request, return the resource collection
        if (! request()->header('X-Inertia') && (request()->wantsJson() || request()->ajax())) {
            return CursoClasseTurnoResource::collection($cursoTutelado->cursoClasses);
        }

        $turnos = Turno::select('id', 'nome')->orderBy('nome')->get();

        // Otherwise render the Inertia page so the frontend can mount
        return Inertia::render('cursos-tutelados/classes/create', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'classesTurnos' => $cursoTutelado->cursoClasses->map(function ($cc) {
                return [
                    'id' => $cc->id,
                    'classe' => [
                        'id' => $cc->classe->id,
                        'nome' => $cc->classe->nome,
                    ],
                    'turnos' => $cc->turnos->map(function ($t) {
                        return [
                            'turno' => [
                                'id' => $t->turno->id,
                                'nome' => $t->turno->nome,
                            ],
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'turnos' => $turnos,
        ]);
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

        return to_route('cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Turnos atualizados com sucesso!',
        ]);
    }
}
