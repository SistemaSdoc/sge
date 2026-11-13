<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap;

use App\Actions\Tenant\GrupoPap\CreateGrupoPap;
use App\Actions\Tenant\Pap\Defesa\DefinirDataDefesa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrupoPap\StoreRequest;
use App\Http\Requests\Tenant\Pap\Defesa\DefinirDataDefesaRequest;
use App\Http\Resources\Tenant\GrupoPap\IndexResource;
use App\Http\Resources\Tenant\Pap\BancaJuriPapResource;
use App\Http\Resources\Tenant\Pap\ElementoGrupoPapResource;
use App\Http\Resources\Tenant\Pap\GrupoPapDetalheResource;
use App\Models\Central\AnoLectivo;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Consultas\PrepararDetalheGrupoPapService;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    use NotificaGrupoPap;

    public function __construct(
        private readonly DefinirDataDefesa $definirDataDefesa,
        private readonly PrepararDetalheGrupoPapService $prepararDetalheGrupoPapService,
    ) {}

    /**
     * Lista os grupos PAP visíveis ao utilizador do colégio.
     */
    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::where('activo', 1)->first()?->id;
        $grupos = GrupoPap::with([
            'professor.user:id,nome',
            'turma.cursoClasseTurno.turno:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ])->when($instituicaoId, fn ($query) => $query->whereHas(
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
            fn ($query) => $query->where('instituicao_id', $instituicaoId)
        ))
            ->when($anoLectivoId, fn ($query) => $query->whereHas(
                'turma',
                fn ($query) => $query->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->latest()->paginate(10)->withQueryString();

        $grupos->getCollection()->transform(function ($grupo) use ($user) {
            $grupo->can = [
                'view' => $user->can('view', $grupo),
                'update' => $user->can('update', $grupo),
                'delete' => $user->can('delete', $grupo),
                'definirData' => $user->can('definirData', $grupo),
            ];

            return $grupo;
        });

        return Inertia::render('tenant/pap/index', [
            'gruposPap' => IndexResource::collection($grupos),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
        ]);
    }

    /**
     * Cria um grupo PAP no contexto do colégio.
     */
    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        CreateGrupoPap $createGrupoPap,
    ) {
        $this->authorize('create', GrupoPap::class);
        $grupo = $createGrupoPap->handle($turma, $request->validated());

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', [
            'colegio' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupo->id,
        ]);
    }

    /**
     * Carrega o grupo PAP no tenant tutelado e prepara os seus detalhes.
     */
    public function show(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicao = Instituicao::findOrFail($user->instituicao_id);
        abort_unless($user->can('grupopap.view'), 403);
        $isReadOnlyCourseSecretary = $user->hasRole('Secretario do Curso');
        $canApprovePapTheme = $user->can('grupopap.aprovar');
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        if ($isReadOnlyCourseSecretary) {
            abort_unless(
                $user->instituicao?->tipo === 'instituto'
                    && $shared->curso_id !== null
                    && $user->cursosSecretariados()
                        ->whereHas('instituicaoCurso', fn ($query) => $query->where('curso_id', $shared->curso_id))
                        ->exists(),
                404,
            );
        }

        $tenantTutelado = Tenant::query()->findOrFail($shared->tenant_tutelado_id);

        return $tenantTutelado->run(function () use (
            $user,
            $instituicao,
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $shared,
            $isReadOnlyCourseSecretary,
            $canApprovePapTheme,
        ) {
            $colegioModel = Instituicao::findOrFail($colegio);
            $cursoTuteladoModel = CursoTutelado::query()
                ->whereKey($cursoTutelado)
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegioModel->id))
                ->when($isReadOnlyCourseSecretary, fn ($query) => $query->whereHas(
                    'instituicaoCurso',
                    fn ($query) => $query->where('curso_id', $shared->curso_id),
                ))
                ->firstOrFail();
            $cursoClasseModel = CursoClasse::query()
                ->whereKey($cursoClasse)
                ->where('curso_tutelado_id', $cursoTuteladoModel->id)
                ->firstOrFail();
            $cursoClasseTurnoModel = CursoClasseTurno::query()
                ->whereKey($cursoClasseTurno)
                ->where('curso_classe_id', $cursoClasseModel->id)
                ->firstOrFail();
            $turmaModel = Turma::query()
                ->whereKey($turma)
                ->where('curso_classe_turno_id', $cursoClasseTurnoModel->id)
                ->firstOrFail();
            $grupoPapModel = GrupoPap::query()
                ->whereKey($grupoPap)
                ->where('turma_id', $turmaModel->id)
                ->firstOrFail();

            return $this->showFromTenant(
                $instituicao,
                $colegioModel,
                $cursoTuteladoModel,
                $cursoClasseModel,
                $cursoClasseTurnoModel,
                $turmaModel,
                $grupoPapModel,
                $user,
                $isReadOnlyCourseSecretary,
                $canApprovePapTheme,
            );
        });
    }

    /**
     * Define a data de defesa do grupo PAP através do contexto do colégio.
     */
    public function definirData(
        DefinirDataDefesaRequest $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can('grupopap.definirData'), 403);
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        Tenant::query()->findOrFail($shared->tenant_tutelado_id)->run(function () use (
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $request,
        ): void {
            $colegioModel = Instituicao::findOrFail($colegio);
            $cursoTuteladoModel = CursoTutelado::query()
                ->whereKey($cursoTutelado)
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegioModel->id))
                ->firstOrFail();
            $cursoClasseModel = CursoClasse::query()
                ->whereKey($cursoClasse)
                ->where('curso_tutelado_id', $cursoTuteladoModel->id)
                ->firstOrFail();
            $cursoClasseTurnoModel = CursoClasseTurno::query()
                ->whereKey($cursoClasseTurno)
                ->where('curso_classe_id', $cursoClasseModel->id)
                ->firstOrFail();
            $turmaModel = Turma::query()
                ->whereKey($turma)
                ->where('curso_classe_turno_id', $cursoClasseTurnoModel->id)
                ->firstOrFail();
            $grupoPapModel = GrupoPap::query()
                ->whereKey($grupoPap)
                ->where('turma_id', $turmaModel->id)
                ->firstOrFail();
            abort_unless($grupoPapModel->status_aprovacao === GrupoPap::APROVACAO_APROVADO, 422);

            $this->definirDataDefesa->handle($grupoPapModel, $request->validated());
            $this->notificarDataDefesaDefinida($grupoPapModel->fresh());
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Data e local da defesa definidos com sucesso!',
        ]);
    }

    /**
     * Prepara a resposta do detalhe do grupo no tenant onde os dados residem.
     */
    private function showFromTenant(
        Instituicao $instituicao,
        Instituicao $colegioModel,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        User $user,
        bool $isReadOnlyCourseSecretary,
        bool $canApprovePapTheme,
    ) {
        $anoLectivoId = $turma->ano_lectivo_id;
        $instituicaoTutoraModel = $instituicao;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;
        $this->prepararDetalheGrupoPapService->prepararDetalhe($grupoPap);
        $cursoTutelado->load(['instituicaoCurso.curso']);
        $trabalho = $grupoPap->trabalhoPap;
        $canManageTheme = $canApprovePapTheme && $grupoPap->podeSerAprovado();
        $canDefineDefenseDate = $user?->can('grupopap.definirData')
            && $grupoPap->status_aprovacao === GrupoPap::APROVACAO_APROVADO;
        $canManageWorkAsCoordination = ! $user->hasAnyRole(['Director', 'Subdirector'])
            && $user?->can('grupopap.aprovar')
            && $trabalho?->podeSerAnalisadoPelaCoordenacao();
        $canViewWorkVersions = $user?->can('grupopap.view') && $trabalho !== null;
        $detalhesPaginados = $this->prepararDetalheGrupoPapService->detalhesPaginados($grupoPap);

        return Inertia::render('tenant/colegio/cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'sigla' => $instituicao->sigla,
            ],
            'colegio' => [
                'id' => $colegioModel->id,
                'nome' => $colegioModel->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome,
            ],
            'cursoClasse' => ['id' => $cursoClasse->id],
            'cursoClasseTurno' => ['id' => $cursoClasseTurno->id],
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new GrupoPapDetalheResource($grupoPap),
            'currentUserId' => (string) $user->getKey(),
            'trabalho' => $this->prepararDetalheGrupoPapService->detalhesTrabalho(
                $grupoPap,
                $instituicaoTutoraModel,
                $nomeCurso,
                $instituicaoTutoraModel->sigla,
            ),
            'historico' => $this->prepararDetalheGrupoPapService->historico(
                $grupoPap,
                (string) $instituicaoTutoraModel->getKey(),
                $nomeCurso,
                $instituicaoTutoraModel->sigla,
            ),
            'banca' => BancaJuriPapResource::collection($detalhesPaginados['banca']),
            'elementos' => ElementoGrupoPapResource::collection($detalhesPaginados['elementos']),
            'can' => [
                'update' => ! $isReadOnlyCourseSecretary && $user?->can('update', $grupoPap),
                'definirData' => ! $isReadOnlyCourseSecretary && $canDefineDefenseDate,
                'delete' => ! $isReadOnlyCourseSecretary && $user?->can('delete', $grupoPap),
                'corrigirTema' => ! $isReadOnlyCourseSecretary && $user?->can('corrigirTema', $grupoPap),
                'aprovar' => ! $isReadOnlyCourseSecretary && $canManageTheme,
                'reprovar' => ! $isReadOnlyCourseSecretary && $canManageTheme,
                'solicitarMelhoria' => ! $isReadOnlyCourseSecretary && $canManageTheme,
                'aprovarComoTutor' => false,
                'solicitarMelhoriaComoTutor' => false,
                'submeter' => ! $isReadOnlyCourseSecretary && $user?->can('submeterTrabalho', $grupoPap),
                'aprovarTrabalhoComoTutor' => false,
                'solicitarCorrecaoComoTutor' => false,
                'aprovarComoCoordenacao' => ! $isReadOnlyCourseSecretary && $canManageWorkAsCoordination,
                'solicitarCorrecaoComoCoordenacao' => ! $isReadOnlyCourseSecretary && $canManageWorkAsCoordination,
                'downloadVersao' => ! $isReadOnlyCourseSecretary && $canViewWorkVersions,
                'elementos' => [
                    'create' => false,
                    'atualizarNota' => ! $isReadOnlyCourseSecretary && $user?->can('elementogrupopap.atualizarNota')
                        && ! is_null($grupoPap->data_defesa)
                        && ! $grupoPap->data_defesa->isFuture()
                        && $grupoPap->jurados()->exists(),
                    'delete' => false,
                ],
                'verBanca' => $isReadOnlyCourseSecretary || $instituicaoTutoraModel?->id === $user->instituicao_id,
                'banca' => [
                    'create' => ! $isReadOnlyCourseSecretary && $user?->can('bancajuripap.create')
                        && ! is_null($grupoPap->data_defesa)
                        && $instituicaoTutoraModel?->id === $user->instituicao_id,
                    'update' => ! $isReadOnlyCourseSecretary && $user?->can('bancajuripap.update')
                        && $instituicaoTutoraModel?->id === $user->instituicao_id,
                    'delete' => ! $isReadOnlyCourseSecretary && $user?->can('bancajuripap.delete')
                        && $instituicaoTutoraModel?->id === $user->instituicao_id,
                ],
            ],
        ]);
    }
}
