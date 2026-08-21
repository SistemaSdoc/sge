<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoClasseTurnoController extends Controller
{
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        $this->authorize('create', CursoClasseTurno::class);

        abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

        return Inertia::render('tenant/cursos-tutelados/classes/create', [
            'instituicao' => [
                'id' => $instituicao->only('id'),
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome,
            ],
            'turnos' => Turno::all(),
        ]);

    }

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        $this->authorize('create', CursoClasseTurno::class);

        abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

        $validated = $request->validate([
            'turnos' => ['required', 'array'],
            'turnos.*' => ['string', 'exists:turnos,id'],
        ]);

        foreach ($validated['turnos'] as $turnoId) {
            $exists = $cursoClasse->turnos()
                ->where('turno_id', $turnoId)
                ->exists();

            if (! $exists) {
                $cursoClasse->turnos()->create([
                    'turno_id' => $turnoId,
                ]);
            }
        }

        return to_route('tenant.dashboard.cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Turnos adicionados com sucesso!',
        ]);
    }
}
