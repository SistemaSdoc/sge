<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Models\AnoLectivo;
use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TurmaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Turma::class);

        $user = Auth::user();
        $professor = $user?->professor;
        $instituicaoId = $user->instituicao_id;

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : (AnoLectivo::query()
                ->where('data_inicio', '<=', now())
                ->where('data_fim', '>=', now())
                ->orderByDesc('data_inicio')
                ->first()?->id
                ?? AnoLectivo::query()
                    ->where('data_inicio', '>', now())
                    ->orderBy('data_inicio')
                    ->first()?->id
                ?? AnoLectivo::query()->orderByDesc('data_inicio')->first()?->id);

        $query = Turma::query();

        $query->whereHas(
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            fn ($q) => $q->where('instituicoes.id', $instituicaoId)
        );

        if ($anoLectivoId) {
            $query->where('ano_lectivo_id', $anoLectivoId);
        }

        if (! $user?->isSuperAdmin() && ! $user?->isDirector()) {
            if (! $professor) {
                return Inertia::render('turmas/index', [
                    'turmas' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                    ],
                    'anosLectivos' => AnoLectivo::query()
                        ->select('id', 'nome')
                        ->orderByDesc('data_inicio')
                        ->get(),
                    'anoLectivoActual' => $anoLectivoId,
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
            'anoLectivo:id,nome',  // ← Carregue o ano
            'alunosActivos',
        ])->paginate(10);

        return Inertia::render('turmas/index', [
            'turmas' => [
                'data' => TurmaResourceIndex::collection($turmas->items())->toArray(request()),
                'current_page' => $turmas->currentPage(),
                'last_page' => $turmas->lastPage(),
            ],
            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
            'can' => [
                'create_turma' => Auth::user()->can('create', Turma::class),
            ],
        ]);
    }
}
