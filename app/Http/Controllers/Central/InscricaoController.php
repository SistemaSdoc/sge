<?php

namespace App\Http\Controllers\Central;

use App\Http\Requests\Inscricao\StoreInscricaoRequest;
use App\Http\Requests\UpdateInscricaoRequest;
use App\Http\Resources\Inscricao\InscricaoResource;
use App\Http\Resources\Inscricao\InscricaoShowResource;
use App\Models\Central\AnoLectivo;
use App\Models\Central\CursoClasseTurno;
use App\Models\Central\Inscricao;
use App\Models\Central\Instituicao;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use App\Services\InscricaoService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InscricaoController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private InscricaoService $inscricaoService
    ) {}

    /**
     * Resolve os labels e flags de acordo com o tipo de instituição do utilizador.
     *
     * @return array{label: string, label_plural: string, tem_nota_teste: bool}
     */
    private function resolveContextoInstituicao(): array
    {
        return [
            'label' => 'Matrícula',
            'label_plural' => 'Matrículas',
            'tem_nota_teste' => true,
        ];
    }

    public function index()
    {
        $this->authorize('viewAny', Inscricao::class);

        $user = Auth::user();
        $instituicaoId = Auth::user()?->instituicaoFiltro();
        $contexto = $this->resolveContextoInstituicao();

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $inscricoes = Inscricao::with([
            'candidato:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'anoLectivo:id,nome',
        ])
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )->when(
                $anoLectivoId,
                fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)
            )->latest()->paginate(10);

        return Inertia::render('inscricoes/index', [
            'inscricoes' => [
                'data' => InscricaoResource::collection($inscricoes->items())->toArray(request()),
                'current_page' => $inscricoes->currentPage(),
                'last_page' => $inscricoes->lastPage(),
            ],
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'anoLectivoActual' => $anoLectivoId,
            'can' => [
                'create' => $user->can('create', Inscricao::class),
            ],
            'entity_label' => $contexto['label'],
            'entity_label_plural' => $contexto['label_plural'],
            'tem_nota_teste' => $contexto['tem_nota_teste'],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Inscricao::class);

        $user = Auth::user();
        $instituicaoId = $user->instituicao_id;
        $contexto = $this->resolveContextoInstituicao();

        $anoLectivoId = request('ano_lectivo_id')
            ?? $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $cursoClasseTurnos = CursoClasseTurno::with([
            'turno:id,nome',
            'cursoClasse.classe:id,nome',
            'cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turmas' => fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)->select('id', 'nome', 'curso_classe_turno_id'),
        ])->whereHas(
            'cursoClasse.cursoTutelado.instituicaoCurso',
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        )->get();

        $cursos = $cursoClasseTurnos
            ->groupBy(function ($cct) {
                return $cct->cursoClasse->cursoTutelado->instituicaoCurso->id;
            })
            ->map(function ($group) {
                $primeiro = $group->first();

                $classes = $group->groupBy(fn ($cct) => $cct->cursoClasse->classe->id)
                    ->map(function ($classGroup) {
                        $firstInClass = $classGroup->first();

                        return [
                            'id' => $firstInClass->cursoClasse->classe->id,
                            'nome' => $firstInClass->cursoClasse->classe->nome,
                            'turnos' => $classGroup->map(fn ($cct) => [
                                'id' => $cct->id,
                                'nome' => $cct->turno->nome,
                                'turmas' => $cct->turmas->map(fn ($t) => [
                                    'id' => $t->id,
                                    'nome' => $t->nome,
                                ])->values(),
                            ])->values(),
                        ];
                    })->values();

                return [
                    'id' => $primeiro->cursoClasse->cursoTutelado->instituicaoCurso->id,
                    'nome' => $primeiro->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
                    'classes' => $classes,
                ];
            })
            ->values();

        $anosLectivos = AnoLectivo::query()
            ->select('id', 'nome', 'data_inicio', 'data_fim')
            ->orderByDesc('data_inicio')
            ->get();

        return Inertia::render('inscricoes/create', [
            'cursos' => $cursos,
            'anosLectivos' => $anosLectivos,
            'anoLectivoId' => $anoLectivoId,
            'anoLectivoActual' => $anoLectivoId,
            'entity_label' => $contexto['label'],
            'entity_label_plural' => $contexto['label_plural'],
            'tem_nota_teste' => $contexto['tem_nota_teste'],
        ]);
    }

    public function store(StoreInscricaoRequest $request)
    {
        $this->authorize('create', Inscricao::class);

        $instituicao = Instituicao::findOrFail(Auth::user()->instituicao_id);

        $this->inscricaoService->criar($request->validated(), $instituicao);

        return redirect()->route('inscricoes.index', [
            'ano_lectivo_id' => $request->validated('ano_lectivo_id') ?? $request->input('ano_lectivo_id'),
        ]);
    }

    public function show(Inscricao $inscricao)
    {
        $this->authorize('view', $inscricao);
        $contexto = $this->resolveContextoInstituicao();

        $inscricao->load([
            'candidato:id,nome,bi,numero_estudante,email,telefone,morada,nacionalidade,naturalidade,filiacao,data_nascimento',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'anoLectivo:id,nome',
        ]);

        return Inertia::render('inscricoes/show', [
            'inscricao' => (new InscricaoShowResource($inscricao))->resolve(),
            'entity_label' => $contexto['label'],
            'entity_label_plural' => $contexto['label_plural'],
            'tem_nota_teste' => $contexto['tem_nota_teste'],
        ]);
    }

    public function update(UpdateInscricaoRequest $request, Inscricao $inscricao)
    {
        $this->authorize('update', $inscricao);

        $this->inscricaoService->atualizarNotaTeste($inscricao, $request->validated('nota_teste'));

        return redirect()->route('inscricoes.index');
    }

    // Cancelar Matrícula de um aluno
    public function destroy(Inscricao $inscricao)
    {
        $this->authorize('cancelar', $inscricao);

        $this->inscricaoService->cancelar($inscricao);

        return redirect()->route('inscricoes.index');
    }

    public function reativar(Inscricao $inscricao)
    {
        $this->authorize('reativar', $inscricao);

        $this->inscricaoService->reativar($inscricao);

        return redirect()->route('inscricoes.index');
    }
}
