<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Helpers\PapHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrupoPap\DefinirDataDefesaRequest;
use App\Http\Requests\Tenant\GrupoPap\StoreRequest;
use App\Http\Resources\Tenant\GrupoPap\BancaResource;
use App\Http\Resources\Tenant\GrupoPap\ElementoResource;
use App\Http\Resources\Tenant\GrupoPap\IndexResource;
use App\Http\Resources\Tenant\GrupoPap\ShowResource;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        // Filtro ano lectivo
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::where('activo', 1)->first()?->id;

        $grupos = GrupoPap::with([
            'professor.user:id,nome',
            'turma.cursoClasseTurno.turno:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ])->when($instituicaoId, fn ($q) => $q->whereHas(
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        ))
            ->when($anoLectivoId, fn ($q) => $q->whereHas(
                'turma',
                fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)   // ← direto na turma, não via cursoClasseTurno
            ))
            ->latest()->paginate(10)->withQueryString();   // ← withQueryString para manter ano_lectivo_id na paginação

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
            'anoLectivoId' => $anoLectivoId,          // ← adicionado
            'anosLectivos' => AnoLectivo::all(),      // ← adicionado
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
        ]);
    }

    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $grupo = GrupoPap::create([
            'turma_id' => $turma->id,
            'professor_tutor_id' => $request->professor_tutor_id,
            'nome_grupo' => $request->nome_grupo,
            'status_aprovacao' => GrupoPap::APROVACAO_RASCUNHO,
            'tema_grupo' => $request->tema_grupo,
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        $grupo->elementos()->createMany(
            collect($request->alunos)->map(fn ($id) => ['aluno_id' => $id])->toArray()
        );

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', [
            'colegio' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupo->id,
        ]);
    }

    public function show(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicao = Instituicao::findOrFail($user->instituicao_id);
        abort_unless($user->can('grupopap.view'), 403);
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();
        $tenantTutelado = Tenant::query()->findOrFail($shared->tenant_tutelado_id);

        return $tenantTutelado->run(function () use ($user, $instituicao, $colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap) {
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

            return $this->showFromTenant(
                $instituicao,
                $colegioModel,
                $cursoTuteladoModel,
                $cursoClasseModel,
                $cursoClasseTurnoModel,
                $turmaModel,
                $grupoPapModel,
                $user,
            );
        });
    }

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
        abort_unless($user->can('grupopap.definirData'), 403);

        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        Tenant::query()->findOrFail($shared->tenant_tutelado_id)->run(function () use ($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap, $request): void {
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

            $grupoPapModel->update([
                'data_defesa' => $request->data_defesa.' '.$request->hora_defesa.':00',
                'local_defesa' => $request->local_defesa,
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Data e local da defesa definidos com sucesso!',
        ]);
    }

    private function showFromTenant(
        Instituicao $instituicao,
        Instituicao $colegioModel,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        User $user,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $anoLectivoId = $turma->ano_lectivo_id;

        $instituicaoTutoraModel = $instituicao;
        $instituicaoTutoraId = $instituicaoTutoraModel?->id;
        $siglaInstituto = $instituicaoTutoraModel?->sigla;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;

        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
        ]);

        $cursoTutelado->load(['instituicaoCurso.curso']);

        // ← NOVO: carregar trabalho tal como na auto tutela
        $trabalho = $grupoPap->trabalhoPap()->with([
            'versoes.submetidoPor:id,nome',
            'versoes.feedbacks.utilizador:id,nome,instituicao_id', // ← instituicao_id
            'aprovadoPor:id,nome,instituicao_id',                  // ← instituicao_id
        ])->first();

        $canManageTheme = $user?->can('grupopap.aprovar')
            && $grupoPap->podeSerAprovado();
        $canDefineDefenseDate = $user?->can('grupopap.definirData')
            && $grupoPap->status_aprovacao === GrupoPap::APROVACAO_APROVADO;
        $canManageWorkAsCoordination = $user?->can('grupopap.aprovar')
            && $trabalho?->podeSerAnalisadoPelaCoordenacao();

        $banca = $grupoPap->jurados()
            ->with('professor.user:id,nome,email')
            ->paginate(10, ['*'], 'page_banca');

        $elementos = $grupoPap->elementos()
            ->with([
                'aluno.inscricao.candidato:id,nome,email',
                'aluno:id,matricula,inscricao_id',
            ])
            ->paginate(10, ['*'], 'page_elementos');

        return Inertia::render(
            'tenant/colegio/cursos-tutelados/classes/turnos/turmas/pap/show',
            [
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
                'grupoPap' => new ShowResource($grupoPap),

                // ← NOVO: serialização idêntica à auto tutela
                'trabalho' => $trabalho ? [
                    'id' => $trabalho->id,
                    'status' => $trabalho->status,
                    'data_aprovacao' => $trabalho->data_aprovacao?->toIso8601String(),
                    'aprovado_por' => $trabalho->aprovadoPor
                        ? PapHelper::nomeAprovador(
                            $trabalho->aprovadoPor,
                            $instituicaoTutoraModel,
                            $nomeCurso,
                        )
                        : $trabalho->aprovado_por_nome,
                    'versoes' => $trabalho->versoes->map(fn ($v) => [
                        'id' => $v->id,
                        'numero_versao' => $v->numero_versao,
                        'nome_original' => $v->nome_original,
                        'status_quando_submetido' => $v->status_quando_submetido,
                        'submetido_por' => $v->submetidoPor?->nome,
                        'created_at' => $v->created_at?->toIso8601String(),
                        'feedbacks' => $v->feedbacks->map(fn ($f) => [
                            'id' => $f->id,
                            'tipo' => $f->tipo,
                            'comentario' => $f->comentario,
                            'utilizador' => $f->utilizador
                                ? PapHelper::nomeAprovador(
                                    $f->utilizador,
                                    $instituicaoTutoraModel,
                                    $nomeCurso,
                                )
                                : $f->utilizador_nome,
                            'created_at' => $f->created_at?->toIso8601String(),
                            'tem_ficheiro_correcao' => ! is_null($f->caminho_ficheiro_correcao),
                            'nome_original_correcao' => $f->nome_original_correcao,
                        ]),
                    ]),
                ] : null,

                'historico' => $grupoPap->historicoAprovacao->map(function ($item) use ($instituicaoTutoraModel, $nomeCurso) {
                    return [
                        'id' => $item->id,
                        'estado_anterior' => $item->estado_anterior,
                        'estado_novo' => $item->estado_novo,
                        'comentario' => $item->comentario,
                        'tema' => $item->tema,
                        'created_at' => $item->created_at?->toIso8601String(),
                        'utilizador' => [
                            'nome' => PapHelper::nomeAprovador(
                                $item->utilizador,
                                $instituicaoTutoraModel,
                                $nomeCurso,
                            ),
                        ],
                    ];
                })->values(),

                'criterios_pap_url' => $cursoTutelado->criterios_pap_path
                    ? Storage::url($cursoTutelado->criterios_pap_path)
                    : null,

                'banca' => BancaResource::collection($banca),
                'elementos' => ElementoResource::collection($elementos),

                'can' => [
                    'update' => $user?->can('update', $grupoPap),
                    'definirData' => $canDefineDefenseDate,
                    'delete' => $user?->can('delete', $grupoPap),
                    'corrigirTema' => $user?->can('corrigirTema', $grupoPap),
                    'aprovar' => $canManageTheme,
                    'reprovar' => $canManageTheme,
                    'solicitarMelhoria' => $canManageTheme,
                    'aprovarComoTutor' => false,
                    'solicitarMelhoriaComoTutor' => false,

                    // ← NOVO: can do trabalho
                    'submeter' => $user?->can('submeterTrabalho', $grupoPap),
                    'aprovarTrabalhoComoTutor' => false,
                    'solicitarCorrecaoComoTutor' => false,
                    'aprovarComoCoordenacao' => $canManageWorkAsCoordination,
                    'solicitarCorrecaoComoCoordenacao' => $canManageWorkAsCoordination,
                    'downloadVersao' => $canManageWorkAsCoordination,

                    'elementos' => [
                        'create' => false,
                        'atualizarNota' => $user?->can('elementogrupopap.atualizarNota')
                            && ! is_null($grupoPap->data_defesa)
                            && ! $grupoPap->data_defesa->isFuture()
                            && $grupoPap->jurados()->exists(),
                        'delete' => false,
                    ],
                    'verBanca' => $instituicaoTutoraModel?->id === $user->instituicao_id,
                    'banca' => [
                        'create' => $user?->can('bancajuripap.create')
                            && ! is_null($grupoPap->data_defesa)
                            && $instituicaoTutoraModel?->id === $user->instituicao_id,
                        'update' => $user?->can('bancajuripap.update')
                            && $instituicaoTutoraModel?->id === $user->instituicao_id,
                        'delete' => $user?->can('bancajuripap.delete')
                            && $instituicaoTutoraModel?->id === $user->instituicao_id,
                    ],
                ],
            ]
        );
    }
}
