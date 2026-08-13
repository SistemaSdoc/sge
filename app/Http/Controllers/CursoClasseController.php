<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CursoClasseController extends Controller
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    /**
     * Display the specified resource (Show page via Inertia).
     */
    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse)
    {
        Redirect::setIntendedUrl(request()->fullUrl());

        $cursoClasse->load(['classe:id,nome', 'turnos.turno:id,nome']);

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        // Se o turno pedido não pertence a este cursoClasse, cai para o primeiro
        $turnoId = $cursoClasse->turnos->firstWhere('id', request('turno'))?->id
            ?? $cursoClasse->turnos->first()?->id;

        $turnoActual = $cursoClasse->turnos->firstWhere('id', $turnoId);

        $turmas = $turnoActual
            ? $turnoActual->turmas()
                ->where('ano_lectivo_id', $anoLectivoId)
                ->withCount('alunosActivos')
                ->orderBy('nome')
                ->paginate(7, ['*'], 'page_turmas')
            : $this->emptyPaginator('page_turmas');

        $disciplinas = $turnoActual
            ? $turnoActual->classeTurnoDisciplinas()
                ->where('ano_lectivo_id', $anoLectivoId)
                ->with('disciplina:id,nome,sigla,componente')
                ->paginate(7, ['*'], 'page_disciplinas')
            : $this->emptyPaginator('page_disciplinas');

        return Inertia::render('cursos-tutelados/classes/show', [
            'instituicao' => ['id' => $instituicao->id, 'nome' => $instituicao->nome],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso->curso->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                ],
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'classe' => ['id' => $cursoClasse->classe->id, 'nome' => $cursoClasse->classe->nome],
                'turnos' => $cursoClasse->turnos->map(fn ($t) => ['id' => $t->id, 'nome' => $t->turno->nome])->toArray(),
                'turnoId' => $turnoId,
                'turmas' => $turmas->through(function (Turma $turma) {
                    return [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                        'alunos_activos_count' => $turma->alunosActivos()->count(),
                        'can' => [
                            'view' => Auth::user()->can('view', $turma),
                            'edit' => Auth::user()->can('update', $turma),
                        ],
                    ];
                }),
                'disciplinas' => $disciplinas,
                'can' => [
                    'create_disciplina' => Auth::user()->can('create', ClasseTurnoDisciplina::class),
                    'create_turma' => Auth::user()->can('create', Turma::class),
                    'create_turno' => Auth::user()->can('create', CursoClasseTurno::class),
                ],
            ],
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }

    private function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 5, 1, [
            'path' => request()->url(),
            'pageName' => $pageName,
        ]);
    }
}
