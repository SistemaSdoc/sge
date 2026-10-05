<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Inscricao\StoreInscricaoRequest;
use App\Http\Requests\Tenant\UpdateInscricaoRequest;
use App\Http\Resources\Tenant\Inscricao\InscricaoResource;
use App\Http\Resources\Tenant\Inscricao\InscricaoShowResource;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\InscricaoService;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $this->authorize('viewAny', Inscricao::class);

        $user = Auth::guard('tenant')->user();
        $instituicaoId = Auth::guard('tenant')->user()?->instituicaoFiltro();
        $secretariadoCourseIds = $user->hasRole('Secretario do Curso')
            ? $user->cursosSecretariados()->select('curso_tutelado.id')
            : null;
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
                $secretariadoCourseIds !== null,
                fn ($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse',
                    fn ($q) => $q->whereIn('curso_tutelado_id', $secretariadoCourseIds),
                )
            )->when(
                $anoLectivoId,
                fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)
            )->search($request->string('search')->toString())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('tenant/inscricoes/index', [
            'inscricoes' => [
                'data' => InscricaoResource::collection($inscricoes->items())->toArray($request),
                'current_page' => $inscricoes->currentPage(),
                'last_page' => $inscricoes->lastPage(),
            ],
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'anoLectivoActual' => $anoLectivoId,
            'filters' => $request->only('search'),
            'can' => [
                'create' => $user->can('create', Inscricao::class)
                    && (! $user->hasRole('Secretario do Curso') || $user->cursosSecretariados()->exists()),
            ],
            'cursosParaMatricula' => $user->hasRole('Secretario do Curso')
                ? $user->cursosSecretariados()
                    ->with('instituicaoCurso.curso:id,nome')
                    ->get()
                    ->map(fn (CursoTutelado $curso): array => [
                        'id' => $curso->getKey(),
                        'nome' => $curso->instituicaoCurso?->curso?->nome ?? 'Curso',
                    ])
                    ->values()
                : [],
            'entity_label' => $contexto['label'],
            'entity_label_plural' => $contexto['label_plural'],
            'tem_nota_teste' => $contexto['tem_nota_teste'],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Inscricao::class);

        $user = Auth::guard('tenant')->user();
        $instituicaoId = $user->instituicao_id;
        $cursoTuteladoId = request()->string('curso_tutelado_id')->toString();
        $contexto = $this->resolveContextoInstituicao();

        if ($user->hasRole('Secretario do Curso')) {
            abort_unless($cursoTuteladoId !== '', 404);

            CursoTutelado::query()
                ->whereKey($cursoTuteladoId)
                ->whereHas('secretarios', fn ($query) => $query->whereKey($user->getKey()))
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $instituicaoId))
                ->firstOrFail();
        }

        $anoLectivoId = request('ano_lectivo_id')
            ?? $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $cursoTuteladoSelecionado = $user->hasRole('Secretario do Curso')
            ? CursoTutelado::query()->findOrFail($cursoTuteladoId)
            : null;

        $cursoClasses = CursoClasse::with([
            'classe:id,nome',
            'cursoTutelado.instituicaoCurso.curso:id,nome,deleted_at',
            'turnos.turno:id,nome',
            'turnos.turmas' => fn ($q) => $q
                ->where('ano_lectivo_id', $anoLectivoId)
                ->select('id', 'nome', 'curso_classe_turno_id'),
        ])->whereHas(
            'cursoTutelado.instituicaoCurso',
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        )->when(
            $user->hasRole('Secretario do Curso'),
            fn ($query) => $query->whereHas(
                'cursoTutelado.secretarios',
                fn ($secretarios) => $secretarios->whereKey($user->getKey()),
            )->whereHas('cursoTutelado', fn ($courses) => $courses->whereKey($cursoTuteladoId)),
        )->get()
            ->reject(fn (CursoClasse $cursoClasse) => $cursoClasse
                ->cursoTutelado?->instituicaoCurso?->curso?->trashed())
            ->values();

        $cursos = $cursoClasses
            ->groupBy(function ($cct) {
                return $cct->cursoTutelado->instituicaoCurso->id;
            })
            ->map(function ($group) {
                $primeiro = $group->first();

                $classes = $group->map(function ($cursoClasse) {
                    return [
                        'id' => $cursoClasse->classe->id,
                        'nome' => $cursoClasse->classe->nome,
                        'turnos' => $cursoClasse->turnos->map(fn ($cursoClasseTurno) => [
                            'id' => $cursoClasseTurno->id,
                            'nome' => $cursoClasseTurno->turno->nome,
                            'turmas' => $cursoClasseTurno->turmas->map(fn ($turma) => [
                                'id' => $turma->id,
                                'nome' => $turma->nome,
                            ])->values(),
                        ])->values(),
                    ];
                })->values();

                return [
                    'id' => $primeiro->cursoTutelado->instituicaoCurso->id,
                    'curso_tutelado_id' => $primeiro->cursoTutelado->id,
                    'nome' => $primeiro->cursoTutelado->instituicaoCurso->curso->nome,
                    'classes' => $classes,
                ];
            })
            ->values();

        $anosLectivos = AnoLectivo::query()
            ->select('id', 'nome', 'data_inicio', 'data_fim')
            ->orderByDesc('data_inicio')
            ->get();

        return Inertia::render('tenant/inscricoes/create', [
            'cursos' => $cursos,
            'anosLectivos' => $anosLectivos,
            'anoLectivoId' => $anoLectivoId,
            'anoLectivoActual' => $anoLectivoId,
            'cursoTuteladoId' => $user->hasRole('Secretario do Curso') ? $cursoTuteladoId : null,
            'cursoInstituicaoId' => $cursoTuteladoSelecionado?->instituicaoCurso?->id,
            'entity_label' => $contexto['label'],
            'entity_label_plural' => $contexto['label_plural'],
            'tem_nota_teste' => $contexto['tem_nota_teste'],
        ]);
    }

    public function store(StoreInscricaoRequest $request)
    {
        $this->authorize('create', Inscricao::class);

        $instituicao = Instituicao::findOrFail(Auth::guard('tenant')->user()->instituicao_id);

        $this->inscricaoService->criar($request->validated(), $instituicao);

        $user = Auth::guard('tenant')->user();

        if ($user->hasRole('Secretario do Curso')) {
            $cursoTutelado = CursoClasseTurno::query()
                ->with('cursoClasse.cursoTutelado')
                ->findOrFail($request->validated('curso_classe_turno_id'))
                ->cursoClasse
                ->cursoTutelado;

            return to_route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
                'instituicao' => $instituicao->getKey(),
                'cursoTutelado' => $cursoTutelado->getKey(),
            ])->with('toast', [
                'type' => 'success',
                'message' => 'Matrícula criada com sucesso.',
            ]);
        }

        return redirect()->route('tenant.dashboard.inscricoes.index', [
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
            'aluno:id,inscricao_id',
        ]);

        return Inertia::render('tenant/inscricoes/show', [
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

        return redirect()->route('tenant.dashboard.inscricoes.index');
    }

    // Cancelar Matrícula de um aluno
    public function destroy(Inscricao $inscricao)
    {
        $this->authorize('cancelar', $inscricao);

        $this->inscricaoService->cancelar($inscricao);

        return redirect()->route('tenant.dashboard.inscricoes.index');
    }

    public function reativar(Inscricao $inscricao)
    {
        $this->authorize('reativar', $inscricao);

        $this->inscricaoService->reativar($inscricao);

        return redirect()->route('tenant.dashboard.inscricoes.index');
    }
}
