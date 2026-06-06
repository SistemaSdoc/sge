<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Http\Resources\Turma\TurmaResourceShow;
use App\Models\Instituicao;
use App\Models\Turma;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TurmaController extends Controller //implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index', only: ['index']),
            new Middleware('permission:turmas.show', only: ['show']),
            new Middleware('permission:turmas.create', only: ['create']),
            new Middleware('permission:turmas.edit', only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy']),
        ];
    }*/
    public function index(Instituicao $instituicao)
    {
        $user = auth()->user();
        $professor = $user?->professor;

        $query = Turma::whereHas(
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
            fn ($q) => $q->where('instituicao_id', $instituicao->id)
        );

        if (! $user?->isSuperAdmin() && ! $user?->isDirector()) {
            if (! $professor) {
                return TurmaResourceIndex::collection(collect());
            }

            $query->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id));
        }

        $turmas = $query->with([
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina:id,nome',
        ])
            ->get();

        return TurmaResourceIndex::collection($turmas);
    }

    public function show(Turma $turma)
    {
        // Carrega relações necessárias antes da Policy para evitar queries extras dentro da Policy
        $turma->load([
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina',
            'alunos.inscricao.candidato',
            'alunos.user',
            'gruposPap',
            'turmaDisciplinaProfessor.professor',
        ]);

        $this->authorize('view', $turma);

        return new TurmaResourceShow($turma);
    }
}
