<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Actions\Tenant\GrupoPap\DeleteGrupoPap;
use App\Actions\Tenant\Pap\Grupo\CreateGrupoPap;
use App\Actions\Tenant\Pap\Grupo\UpdateGrupoPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Grupo\ActualizarGrupoPapRequest;
use App\Http\Requests\Tenant\Pap\Grupo\CriarGrupoPapIndependenteRequest;
use App\Http\Requests\Tenant\Pap\Grupo\CriarGrupoPapRequest;
use App\Http\Resources\Tenant\Pap\BancaJuriPapResource;
use App\Http\Resources\Tenant\Pap\ElementoGrupoPapResource;
use App\Http\Resources\Tenant\Pap\GrupoPapCriacaoResource;
use App\Http\Resources\Tenant\Pap\GrupoPapDetalheResource;
use App\Http\Resources\Tenant\Pap\GrupoPapEdicaoResource;
use App\Http\Resources\Tenant\Pap\GrupoPapListagemResource;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Notifications\Pap\GrupoCriadoNotification;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\Pap\Consultas\ListarGruposPapService;
use App\Services\Tenant\Pap\Consultas\PrepararDetalheGrupoPapService;
use App\Services\Tenant\Pap\Consultas\PrepararFormularioGrupoPapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    /**
     * Recebe os serviços de consulta e as operações de escrita do grupo.
     */
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly ListarGruposPapService $listarGruposPapService,
        private readonly PrepararFormularioGrupoPapService $prepararFormularioGrupoPapService,
        private readonly PrepararDetalheGrupoPapService $prepararDetalheGrupoPapService,
        private readonly CreateGrupoPap $createGrupoPap,
        private readonly UpdateGrupoPap $updateGrupoPap,
        private readonly DeleteGrupoPap $deleteGrupoPap,
    ) {}

    /**
     * Lista os grupos PAP acessíveis ao utilizador.
     */
    public function index(Request $request)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('viewAny', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $isReadOnlyCourseSecretary = $user->hasRole('Secretario do Curso');
        $search = $request->string('search')->toString();
        $anoLectivoId = filled($request->input('ano_lectivo_id'))
            ? $request->input('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();
        $instituicaoIdFiltro = $request->input('instituicao_id');

        if (
            ! $instituicaoIdFiltro
            && ! ($user->hasRole('Secretario do Curso') && $user->instituicao?->tipo === 'instituto')
        ) {
            $instituicaoIdFiltro = $user->instituicao_id;
        }

        $cursoIdFiltro = $request->input('curso_id') ?: null;
        $grupos = $this->listarGruposPapService->listar(
            $user,
            $anoLectivoId,
            $instituicaoIdFiltro,
            $cursoIdFiltro,
            $search,
        );

        $grupos->transform(function ($grupo) use ($user, $isReadOnlyCourseSecretary) {
            $grupo->can = [
                'view' => $user->can('view', $grupo),
                'update' => ! $isReadOnlyCourseSecretary && $user->can('update', $grupo),
                'delete' => ! $isReadOnlyCourseSecretary && $user->can('delete', $grupo),
                'definirData' => ! $isReadOnlyCourseSecretary && $user->can('definirData', $grupo),
                'definirTema' => ! $isReadOnlyCourseSecretary && $user->can('definirTema', $grupo),
            ];

            return $grupo;
        });

        $cursosTutelados = $this->listarGruposPapService->cursosTutelados($user, $instituicaoIdFiltro);
        $cursosFiltro = $this->listarGruposPapService->opcoesFiltroCurso($cursosTutelados);
        $instituicoes = $this->listarGruposPapService->instituicoesPAP($user);

        return Inertia::render('tenant/pap/index', [
            'instituicao' => [
                'id' => $user->instituicao->id,
                'nome' => $user->instituicao->nome,
            ],
            'instituicoes' => $instituicoes,
            'cursosTutelados' => $cursosTutelados,
            'cursosFiltro' => $cursosFiltro,
            'gruposPap' => [
                'data' => GrupoPapListagemResource::collection($grupos)->resolve(),
            ],
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'filters' => [
                'search' => $search,
                'instituicao_id' => (string) ($instituicaoIdFiltro ?? ''),
                'curso_id' => (string) ($cursoIdFiltro ?? ''),
                'ano_lectivo_id' => (string) ($anoLectivoId ?? ''),
            ],
            'can' => [
                'create' => ! $isReadOnlyCourseSecretary && $user->can('create', GrupoPap::class),
                'selecionarInstituicao' => ($isReadOnlyCourseSecretary && $user->instituicao?->tipo === 'instituto')
                    || $user->can('selecionarInstituicao', GrupoPap::class),
                'selecionarTodasInstituicoes' => $isReadOnlyCourseSecretary
                    && $user->instituicao?->tipo === 'instituto',
                'selecionarAnoLectivo' => $isReadOnlyCourseSecretary
                    || $user->can('selecionarAnoLectivo', GrupoPap::class),
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
        Turma $turma,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        $options = $this->prepararFormularioGrupoPapService->opcoesCriacao($cursoTutelado, $turma);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id', 'nome'),
            'cursoClasse' => $cursoClasse->only('id', 'nome'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new GrupoPapCriacaoResource((object) $options),
        ]);
    }

    /**
     * Cria um grupo PAP e os seus elementos.
     */
    public function store(
        CriarGrupoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);
        $grupo = $this->createGrupoPap->handle($turma, $request->validated());
        $alunosUsers = $grupo->alunos->map->user->filter();
        Notification::send($alunosUsers, new GrupoCriadoNotification($grupo));

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
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('view', $grupoPap);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $isReadOnlyCourseSecretary = $user->hasRole('Secretario do Curso');
        $this->prepararDetalheGrupoPapService->prepararDetalhe($grupoPap);
        $instituicaoTutora = $grupoPap->instituicaoTutora();
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;
        $detalhes = $this->prepararDetalheGrupoPapService->detalhesPaginados($grupoPap);
        $can = fn (string $ability): bool => ! $isReadOnlyCourseSecretary
            && $user->can($ability, $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new GrupoPapDetalheResource($grupoPap),
            'currentUserId' => (string) $user->getKey(),
            'historico' => $this->prepararDetalheGrupoPapService->historico(
                $grupoPap,
                $instituicaoTutora?->id,
                $nomeCurso,
                $instituicaoTutora?->sigla,
            ),
            'trabalho' => $this->prepararDetalheGrupoPapService->detalhesTrabalho(
                $grupoPap,
                $instituicaoTutora,
                $nomeCurso,
                $instituicaoTutora?->sigla,
            ),
            'banca' => BancaJuriPapResource::collection($detalhes['banca']),
            'elementos' => ElementoGrupoPapResource::collection($detalhes['elementos']),
            'can' => [
                'update' => $can('update'),
                'definirData' => $can('definirData'),
                'delete' => $can('delete'),
                'corrigirTema' => $can('corrigirTema'),
                'aprovar' => $can('aprovar'),
                'reprovar' => $can('reprovar'),
                'solicitarMelhoria' => $can('solicitarMelhoria'),
                'definirTema' => $can('definirTema'),
                'aprovarComoTutor' => $can('aprovarComoTutor'),
                'submeter' => $can('submeterTrabalho'),
                'aprovarTrabalhoComoTutor' => $can('aprovarTrabalhoComoTutor'),
                'solicitarCorrecaoComoTutor' => $can('solicitarCorrecaoTrabalhoComoTutor'),
                'aprovarComoCoordenacao' => $can('aprovarTrabalhoComoCoordenacao'),
                'solicitarCorrecaoComoCoordenacao' => $can('solicitarCorrecaoTrabalhoComoCoordenacao'),
                'downloadVersao' => $can('downloadVersaoTrabalho'),
                'elementos' => [
                    'create' => ! $isReadOnlyCourseSecretary && $user->can('elementogrupopap.create'),
                    'atualizarNota' => ! $isReadOnlyCourseSecretary
                        && $user->can('elementogrupopap.atualizarNota')
                        && $instituicaoTutora?->id === $user->instituicao_id
                        && $grupoPap->data_defesa !== null
                        && ! $grupoPap->data_defesa->isFuture()
                        && $grupoPap->jurados()->exists(),
                    'delete' => ! $isReadOnlyCourseSecretary && $user->can('elementogrupopap.delete'),
                ],
                'verBanca' => $isReadOnlyCourseSecretary
                    || ($instituicaoTutora?->id === $user->instituicao_id && ! $user->hasRole('Aluno')),
                'banca' => [
                    'create' => ! $isReadOnlyCourseSecretary
                        && $user->can('create', [BancaJuriPap::class, $grupoPap])
                        && $instituicaoTutora?->id === $user->instituicao_id,
                    'update' => ! $isReadOnlyCourseSecretary
                        && $user->can('bancajuripap.update')
                        && $instituicaoTutora?->id === $user->instituicao_id,
                    'delete' => ! $isReadOnlyCourseSecretary
                        && $user->can('bancajuripap.delete')
                        && $instituicaoTutora?->id === $user->instituicao_id,
                ],
            ],
        ]);
    }

    /**
     * Apresenta o formulário de edição do grupo PAP.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        Gate::forUser($user)->authorize('update', $grupoPap);
        $options = $this->prepararFormularioGrupoPapService->opcoesEdicao($cursoTutelado, $turma, $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new GrupoPapEdicaoResource((object) [
                'professores' => $options['professores'],
                'alunos' => $options['alunos'],
                'grupoPap' => $grupoPap,
            ]),
            'podeAlterarTutor' => $grupoPap->professor_tutor_id !== null
                && $user->can('alterarTutor', $grupoPap),
        ]);
    }

    /**
     * Actualiza os dados e os elementos de um grupo PAP.
     */
    public function update(
        ActualizarGrupoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('update', $grupoPap);
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
    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('delete', $grupoPap);

        if ($grupoPap->elementos()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Não é possível remover o grupo enquanto tiver alunos. Remova primeiro todos os alunos.',
            ]);

            return back();
        }

        $this->deleteGrupoPap->handle($grupoPap);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Grupo PAP removido com sucesso.',
        ]);

        return to_route('tenant.dashboard.grupos-pap.index');
    }

    /**
     * Devolve as classes de 13.º ano para o curso tutelado seleccionado.
     */
    public function classes(Request $request, Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        return response()->json(
            $this->prepararFormularioGrupoPapService->classesParaGrupo((string) $request->input('curso_tutelado_id'))
        );
    }

    /**
     * Devolve os turnos para a classe seleccionada.
     */
    public function turnos(Request $request, Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        return response()->json(
            $this->prepararFormularioGrupoPapService->turnosParaGrupo((string) $request->input('curso_classe_id'))
        );
    }

    /**
     * Devolve as turmas para o turno seleccionado.
     */
    public function turmas(Request $request, Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);
        $anoLectivoId = filled($request->input('ano_lectivo_id'))
            ? (string) $request->input('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        return response()->json(
            $this->prepararFormularioGrupoPapService->turmasParaGrupo(
                (string) $request->input('curso_classe_turno_id'),
                $anoLectivoId,
            )
        );
    }

    /**
     * Devolve professores e alunos disponíveis para o grupo PAP a criar.
     */
    public function formOptions(Request $request, Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        return response()->json(
            $this->prepararFormularioGrupoPapService->opcoesFormularioIndependente(
                (string) $request->input('curso_tutelado_id'),
                (string) $request->input('turma_id'),
            )
        );
    }

    /**
     * Devolve os alunos disponíveis para uma turma.
     */
    public function alunosDisponiveis(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        return response()->json(
            $this->prepararFormularioGrupoPapService->alunosDisponiveisParaTurma($turma)
        );
    }

    /**
     * Apresenta o formulário independente de criação de grupo PAP.
     */
    public function createIndependente(Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/pap/create', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursosTutelados' => $this->listarGruposPapService->cursosTutelados($user)
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
     * Cria um grupo PAP a partir do formulário independente.
     */
    public function storeIndependente(CriarGrupoPapIndependenteRequest $request, Instituicao $instituicao)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('create', GrupoPap::class);
        $validated = $request->validated();
        $turma = Turma::with('cursoClasseTurno.cursoClasse')->findOrFail($validated['turma_id']);
        $grupo = $this->createGrupoPap->handle($turma, $validated);
        $alunosUsers = $grupo->alunos->map->user->filter();
        Notification::send($alunosUsers, new GrupoCriadoNotification($grupo));

        return to_route('tenant.dashboard.grupos-pap.index', [
            'ano_lectivo_id' => $turma->ano_lectivo_id,
        ])->with('toast', ['type' => 'success', 'message' => 'Grupo PAP criado com sucesso!']);
    }
}
