<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Models\Turma;

class TurmaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Turma::class);

        $user = auth()->user();
        
        $professor = $user?->professor;

        $query = Turma::query();

        if (! $user?->isSuperAdmin() && ! $user?->isDirector()) {
            if (! $professor) {
                return inertia('turmas/index', ['turmas' => []]);
            }

            $query->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id));
        }

        $turmas = $query->with([
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina:id,nome',
            'alunosActivos',
        ])->paginate(10);

        return inertia('turmas/index', [
            'turmas' => [
                'data' => TurmaResourceIndex::collection($turmas->items())->toArray(request()),
                'current_page' => $turmas->currentPage(),
                'last_page' => $turmas->lastPage(),
            ],
        ]);
    }
}
