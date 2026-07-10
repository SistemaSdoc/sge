<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Models\Turma;
use Illuminate\Support\Facades\Auth;

class TurmaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Turma::class);

        $user = Auth::user();
        $professor = $user?->professor;
        $instituicaoId = $user->instituicao_id;

        $query = Turma::query();

        $query->whereHas(
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            fn ($q) => $q->where('instituicoes.id', $instituicaoId)
        );

        if (! $user?->isSuperAdmin() && ! $user?->isDirector()) {
            if (! $professor) {
                return inertia('turmas/index', [
                    'turmas' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                    ],
                    'can' => [
                        'create_turma' => Auth::user()->can('create', Turma::class),
                    ],
                ]);
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
            'can' => [
                'create_turma' => Auth::user()->can('create', Turma::class),
            ],
        ]);
    }
}
