<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
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
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        $this->authorize('view', $cursoClasse);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

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
            ->through(function (Turma $turma) use ($user) {
                return [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'alunos_activos_count' => $turma->alunosActivos()->count(),
                    'can' => [
                        'view' => $user->can('view', $turma),
                        'edit' => $user->can('update', $turma),
                    ],
                ];
            })
        : $this->emptyPaginator('page_turmas');

        $disciplinas = $turnoActual
            ? $turnoActual->classeTurnoDisciplinas()
                ->where('ano_lectivo_id', $anoLectivoId)
                ->with('disciplina:id,nome,sigla,componente')
                ->paginate(7, ['*'], 'page_disciplinas')
            : $this->emptyPaginator('page_disciplinas');

        // Formatar turnos
        $turnos = $cursoClasse->turnos->map(fn ($t) => [
            'id' => $t->id,
            'nome' => $t->turno->nome,
        ])->toArray();

        // Formatar permissions
        $permissions = [
            'curso' => [
                'view' => $user->can('view', $cursoTutelado),
            ],
            'classe' => [
                'view' => $user->can('view', $cursoClasse),
            ],
            'turno' => [
                'create' => $user->can('create', CursoClasseTurno::class),
            ],
            'disciplina' => [
                'create' => $user->can('create', ClasseTurnoDisciplina::class),
            ],
            'turma' => [
                'create' => $user->can('create', Turma::class),
            ],
        ];

        // Formatar anos lectivos
        $anosLectivos = AnoLectivo::query()->select('id', 'nome')->orderByDesc('data_inicio')->get();

        return Inertia::render('tenant/cursos-tutelados/classes/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome,
                'turnos' => $turnos,
                'turnoId' => $turnoId,
                'turmas' => $turmas,
                'disciplinas' => $disciplinas,
            ],
            'anosLectivos' => $anosLectivos,
            'anoLectivoActual' => $anoLectivoId,
            'can' => $permissions,
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
