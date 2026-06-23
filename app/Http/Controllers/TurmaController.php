<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Http\Resources\Turma\TurmaResourceShow;
use App\Models\Turma;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TurmaController extends Controller // implements HasMiddleware
{
    /*
    public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index', only: ['index']),
            new Middleware('permission:turmas.show', only: ['show']),
            new Middleware('permission:turmas.create', only: ['create']),
            new Middleware('permission:turmas.edit', only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy']),
        ];
    }
*/
    public function index()
    {
        $user = auth()->user();
        $professor = $user?->professor;

        $query = Turma::query();

        if (!$user?->isSuperAdmin() && !$user?->isDirector()) {
            if (!$professor) {
                return inertia('turmas/index', ['turmas' => []]);
            }

            $query->whereHas('turmaDisciplinaProfessor', fn($q) => $q->where('professor_id', $professor->id));
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

    public function show(Turma $turma)
    {
        $turma->load([
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina',
            'alunos.inscricao.candidato',
            'alunos.user',
            'gruposPap',
            'turmaDisciplinaProfessor.professor',
        ]);

        return inertia('turmas/show', [
            'turma' => new TurmaResourceShow($turma),
        ]);
    }
}
