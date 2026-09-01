<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\GrupoPap\CreateGrupoPap;
use App\Actions\Tenant\GrupoPap\DefinirDataDefesa;
use App\Actions\Tenant\GrupoPap\DeleteGrupoPap;
use App\Actions\Tenant\GrupoPap\UpdateGrupoPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrupoPap\ActualizarTemaRequest;
use App\Http\Requests\Tenant\GrupoPap\DefinirDataDefesaRequest;
use App\Http\Requests\Tenant\GrupoPap\StoreRequest;
use App\Http\Requests\Tenant\GrupoPap\UpdateRequest;
use App\Http\Requests\Tenant\StoreIndependenteRequest;
use App\Http\Resources\Tenant\GrupoPap\BancaResource;
use App\Http\Resources\Tenant\GrupoPap\CreateResource;
use App\Http\Resources\Tenant\GrupoPap\EditResource;
use App\Http\Resources\Tenant\GrupoPap\ElementoResource;
use App\Http\Resources\Tenant\GrupoPap\IndexResource;
use App\Http\Resources\Tenant\GrupoPap\ShowResource;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\GrupoPap\GrupoPapService;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class GrupoPapController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly GrupoPapViewService $grupoPapViewService,
        private readonly GrupoPapService $cascataService,   /* ← novo */
        private readonly CreateGrupoPap $createGrupoPap,
        private readonly UpdateGrupoPap $updateGrupoPap,
        private readonly DeleteGrupoPap $deleteGrupoPap,
        private readonly DefinirDataDefesa $definirDataDefesa
    ) {}

    /**
     * Lista os grupos PAP acessíveis ao utilizador.
     */
    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $instituicaoIdFiltro = request('instituicao_id') ?: $user->instituicao_id;
        $cursoTuteladoIdFiltro = request('curso_tutelado_id') ?: null;

        $grupos = $this->grupoPapViewService->index($user, $anoLectivoId, $instituicaoIdFiltro, $cursoTuteladoIdFiltro);

        $grupos->getCollection()->transform(function ($grupo) use ($user) {
            $grupo->can = [
                'view' => $user->can('view', $grupo),
                'update' => $user->can('update', $grupo),
                'delete' => $user->can('delete', $grupo),
                'definirData' => $user->can('definirData', $grupo),
                'definirTema' => $user->can('definirTema', $grupo),
            ];

            return $grupo;
        });

        $cursosTutelados = $this->grupoPapViewService->tutoredCourses($user, $instituicaoIdFiltro);
        $instituicoes = $this->grupoPapViewService->papInstitutions($user);

        return Inertia::render('tenant/pap/index', [
            'instituicao' => [
                'id' => $user->instituicao->id,
                'nome' => $user->instituicao->nome,
            ],
            'instituicoes' => $instituicoes,
            'cursosTutelados' => $cursosTutelados,
            'gruposPap' => IndexResource::collection($grupos),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
        ]);
    }

    /**
     * Apresenta o formulário de criação de um grupo PAP.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $anoLectivoId = $turma->ano_lectivo_id;
        $options = $this->grupoPapViewService->createOptions($cursoTutelado, $turma);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id', 'nome'),
            'cursoClasse' => $cursoClasse->only('id', 'nome'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new CreateResource((object) [
                'professores' => $options['professores'],
                'alunos' => $options['alunos'],
            ]),
        ]);
    }

    /**
     * Cria um grupo PAP e os seus elementos.
     */
    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $grupo = $this->createGrupoPap->handle($turma, $request->validated());

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupo->id,
        ]);
    }

    /**
     * Apresenta os detalhes, elementos e banca de um grupo PAP.
     */
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('view', $grupoPap);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $anoLectivoId = $turma->ano_lectivo_id;

        $this->grupoPapViewService->prepareShow($grupoPap);

        $instituicaoTutoraModel = $grupoPap->instituicaoTutora();
        $instituicaoTutoraId = $instituicaoTutoraModel?->id;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;
        $siglaInstituto = $instituicaoTutoraModel?->sigla;

        $detalhes = $this->grupoPapViewService->paginatedDetails($grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new ShowResource($grupoPap),
            'historico' => $this->grupoPapViewService->history(
                $grupoPap,
                $instituicaoTutoraId,
                $nomeCurso,
                $siglaInstituto,
            ),
            'banca' => BancaResource::collection($detalhes['banca']),
            'elementos' => ElementoResource::collection($detalhes['elementos']),
            'can' => [
                'update' => $user?->can('update', $grupoPap),
                'definirData' => $user?->can('definirData', $grupoPap),
                'delete' => $user?->can('delete', $grupoPap),
                'corrigirTema' => $user?->can('corrigirTema', $grupoPap),
                'aprovar' => $user?->can('aprovar', $grupoPap),
                'reprovar' => $user?->can('reprovar', $grupoPap),
                'solicitarMelhoria' => $user?->can('solicitarMelhoria', $grupoPap),
                'definirTema' => $user->can('definirTema', $grupoPap),
                'aprovarComoTutor' => $user?->can('aprovarComoTutor', $grupoPap),
                'elementos' => [
                    'create' => $user?->can('elementogrupopap.create'),
                    'atualizarNota' => $user?->can('elementogrupopap.atualizarNota')
                        && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
                        && ! is_null($grupoPap->data_defesa)
                        && ! $grupoPap->data_defesa->isFuture()
                        && $grupoPap->jurados()->exists(),
                    'delete' => $user?->can('elementogrupopap.delete'),
                ],
                // 'verBanca' => $grupoPap->instituicaoTutora()?->id === $user->instituicao_id,
                'verBanca' => $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
                    && ! $user->hasRole('Aluno'),
                'banca' => [
                    'create' => $user?->can('create', [BancaJuriPap::class, $grupoPap]),
                    'update' => $user?->can('bancajuripap.update'),
                    'delete' => $user?->can('bancajuripap.delete'),
                ],
            ],
        ]);
    }

    /**
     * Mostra o formulário para editar os dados de um grupo da PAP.
     */
    /**
     * Apresenta o formulário de edição de um grupo PAP.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('update', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        $options = $this->grupoPapViewService->editOptions($cursoTutelado, $turma, $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new EditResource((object) [
                'professores' => $options['professores'],
                'alunos' => $options['alunos'],
                'grupoPap' => $grupoPap,
            ]),
        ]);
    }

    /**
     * Actualiza os dados e os elementos de um grupo PAP.
     */
    public function update(
        UpdateRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('update', $grupoPap);

        $this->updateGrupoPap->handle($grupoPap, $request->validated());

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Grupo PAP actualizado com sucesso!',
        ]);
    }

    /**
     * Remove um grupo PAP e os seus registos dependentes.
     */
    public function destroy(GrupoPap $grupoPap)
    {
        $this->authorize('delete', $grupoPap);

        $this->deleteGrupoPap->handle($grupoPap);

        return response()->json(['message' => 'Grupo PAP removido com sucesso.']);
    }

    /**
     * Define a data, hora e local da defesa.
     */
    public function definirData(
        DefinirDataDefesaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('definirData', $grupoPap);

        $this->definirDataDefesa->handle($grupoPap, $request->validated());

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Data e local da defesa definidos com sucesso!',
        ]);
    }

    /**
     * Apresenta o formulário para corrigir o tema do grupo.
     */
    public function editarTema(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('corrigirTema', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/editar-tema', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new ShowResource($grupoPap),
        ]);
    }

    /**
     * Guarda a correcção do tema e devolve o grupo ao tutor.
     */
    public function actualizarTema(
        ActualizarTemaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('corrigirTema', $grupoPap);

        $validated = $request->validated();

        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Tema corrigido. Aguarda revisão do professor tutor.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Endpoints auxiliares do formulário independente           */
    /* ------------------------------------------------------------------ */

    /** Devolve as classes de 13.º ano para o curso tutelado seleccionado. */
    public function classes(Request $request, Instituicao $instituicao)
    {
        return response()->json(
            $this->cascataService->classes((string) $request->input('curso_tutelado_id'))
        );
    }

    /** Devolve os turnos para a classe seleccionada. */
    public function turnos(Request $request, Instituicao $instituicao)
    {
        return response()->json(
            $this->cascataService->turnos((string) $request->input('curso_classe_id'))
        );
    }

    /** Devolve as turmas para o turno seleccionado. */
    public function turmas(Request $request, Instituicao $instituicao)
    {
        return response()->json(
            $this->cascataService->turmas((string) $request->input('curso_classe_turno_id'))
        );
    }

    /** Devolve professores e alunos disponíveis para o grupo PAP a criar. */
    public function formOptions(Request $request, Instituicao $instituicao)
    {
        return response()->json(
            $this->cascataService->formOptions(
                (string) $request->input('curso_tutelado_id'),
                (string) $request->input('turma_id'),
            )
        );
    }


    /**
     * Formulário de criação de grupo PAP sem turma pré-seleccionada.
     */
    public function createIndependente(Instituicao $instituicao)
    {
        $this->authorize('create', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/pap/create', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursosTutelados' => $this->grupoPapViewService->tutoredCourses($user)
                ->map(fn (array $curso): array => [
                    'id' => $curso['id'],
                    'nome' => $curso['nome'],
                ])
                ->values(),
            'classes' => [],
            'turnos' => [],
            'turmas' => [],
            'professores' => [],
            'alunos' => [],
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
        ]);
    }

    /**
     * Cria um grupo PAP indicando explicitamente a turma no payload.
     */
    /**
     * Cria um grupo PAP a partir do formulário independente (sem turma na URL).
     * Resolve a turma pelo payload e delega na mesma action do fluxo normal.
     */
    public function storeIndependente(StoreIndependenteRequest $request, Instituicao $instituicao)
    {
        $this->authorize('create', GrupoPap::class);

        $validated = $request->validated();
        $turma = Turma::with('cursoClasseTurno.cursoClasse')->findOrFail($validated['turma_id']);

        $this->createGrupoPap->handle($turma, $validated);

        return to_route('tenant.dashboard.grupos-pap.index', [
            'ano_lectivo_id' => $turma->ano_lectivo_id,
        ])->with('toast', ['type' => 'success', 'message' => 'Grupo PAP criado com sucesso!']);
    }
}
