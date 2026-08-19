<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TurmaController extends Controller
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function index()
    {
        $this->authorize('viewAny', Turma::class);

        Redirect::setIntendedUrl(request()->fullUrl());

        $user = Auth::user();
        $professor = $user?->professor;
        $instituicaoId = $user->instituicao_id;

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $cursos = CursoTutelado::query()
            ->whereHas('instituicaoCurso', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with('instituicaoCurso.curso:id,nome')
            ->get()
            ->map(fn ($ct) => [
                'id' => $ct->id,
                'nome' => $ct->instituicaoCurso?->curso?->nome,
            ]);

        $cursoClasses = CursoClasse::query()
            ->whereHas('cursoTutelado.instituicaoCurso', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with('classe:id,nome')
            ->get()
            ->map(fn ($cc) => [
                'id' => $cc->id,
                'curso_tutelado_id' => $cc->curso_tutelado_id,
                'nome' => $cc->classe?->nome,
            ]);

        $query = Turma::query()
            ->whereHas(
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
                    'cursos' => $cursos,
                    'classes' => $cursoClasses,
                    'turnos' => [],
                    'can' => [
                        'create_turma' => $user->can('create', Turma::class),
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
            'anoLectivo:id,nome',
            'alunosActivos',
        ])->paginate(10);

        return Inertia::render('turmas/index', [
            'instituicaoId' => $instituicaoId,
            'turmas' => [
                'data' => TurmaResourceIndex::collection($turmas->items())->toArray(request()),
                'current_page' => $turmas->currentPage(),
                'last_page' => $turmas->lastPage(),
            ],
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'anoLectivoActual' => $anoLectivoId,
            'cursos' => $cursos,
            'classes' => $cursoClasses,
            'turnos' => Inertia::defer(fn () => CursoClasseTurno::query()
                ->where('curso_classe_id', request('curso_classe_id'))
                ->with('turno:id,nome')
                ->get()
                ->map(fn ($cct) => [
                    'id' => $cct->id,
                    'nome' => $cct->turno?->nome,
                ])
            ),
            'can' => [
                'create' => $user->can('create', Turma::class),
            ],
        ]);
    }
}
