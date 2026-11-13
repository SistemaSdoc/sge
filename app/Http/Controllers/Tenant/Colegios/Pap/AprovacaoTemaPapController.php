<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap;

use App\Actions\Tenant\Pap\Tema\ReenviarTemaPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ActualizarTemaPapPeloColegioRequest;
use App\Http\Requests\Tenant\Pap\Aprovacao\AprovarTemaPapRequest;
use App\Http\Requests\Tenant\Pap\Aprovacao\ReenviarTemaPapPeloColegioRequest;
use App\Http\Requests\Tenant\Pap\Aprovacao\ReprovarTemaPapRequest;
use App\Http\Requests\Tenant\Pap\Aprovacao\SolicitarMelhoriaTemaPapRequest;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Pap\Aprovacao\DecisaoTemaCoordenacaoService;
use App\Services\Tenant\Pap\Aprovacao\DecisaoTemaTutorService;
use App\Services\Tenant\Pap\Consultas\ListarTemasPendentesCoordenacaoService;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use App\Services\Tenant\Tutela\TutelaService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AprovacaoTemaPapController extends Controller
{
    /**
     * Recebe os serviços de aprovação e de acesso entre tenants.
     */
    public function __construct(
        private readonly ListarTemasPendentesCoordenacaoService $listarTemasPendentesService,
        private readonly DecisaoTemaTutorService $decisaoTemaTutorService,
        private readonly DecisaoTemaCoordenacaoService $decisaoTemaCoordenacaoService,
        private readonly TemaPapUnicidadeService $temaPapUnicidadeService,
        private readonly ReenviarTemaPap $reenviarTemaPap,
        private readonly TutelaService $tutelaService,
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Autoriza uma decisão PAP do actor no tenant activo.
     */
    private function autorizarDecisaoPap(User $user, string $permission): void
    {
        abort_unless(
            ! $user->hasAnyRole(['Director', 'Subdirector']) && $user->can($permission),
            403,
        );
    }

    /**
     * Lista os temas pendentes de aprovação pelo coordenador do curso.
     */
    public function pendentes(Instituicao $colegio)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can('grupopap.aprovar'), 403);

        if (! $user->professor) {
            return inertia('tenant/pap/PendentesAprovacao', [
                'temasPendentes' => [],
                'rotaAprovar' => null,
                'rotaReprovar' => null,
                'rotaMelhoria' => null,
            ]);
        }

        $temasPendentes = $this->listarTemasPendentesService->listar($user);

        return inertia('tenant/pap/PendentesAprovacao', [
            'temasPendentes' => $temasPendentes,
            'rotaAprovar' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.aprovar', [
                'colegio' => $colegio->id,
                'grupoPap' => ':id',
            ]),
            'rotaReprovar' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.reprovar', [
                'colegio' => $colegio->id,
                'grupoPap' => ':id',
            ]),
            'rotaMelhoria' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.solicitar-melhoria', [
                'colegio' => $colegio->id,
                'grupoPap' => ':id',
            ]),
        ]);
    }

    /**
     * Apresenta à coordenação o formulário para pedir uma melhoria.
     */
    public function formSolicitarMelhoriaCoordenacao(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can('grupopap.solicitarMelhoria'), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $params = compact('colegio', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');
        $proposta = $this->withExternalGrupo(
            $colegio,
            (string) $cursoTutelado->getKey(),
            (string) $cursoClasse->getKey(),
            (string) $cursoClasseTurno->getKey(),
            (string) $turma->getKey(),
            (string) $grupoPap->getKey(),
            $user,
            function (GrupoPap $grupo): array {
                abort_unless($grupo->podeSerAprovado(), 403);

                return [
                    'grupoPap' => $grupo->only(
                        'id',
                        'nome_grupo',
                        'tema_grupo',
                        'problema',
                        'objectivo_geral',
                        'objectivos_especificos',
                    ),
                    'turma' => $grupo->turma?->only('nome'),
                ];
            },
            $tenantTutorId,
        );

        return Inertia::render('tenant/pap/decisao-coordenacao/solicitar-melhoria', [
            ...$proposta,
            'submitUrl' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.solicitar-melhoria', $params),
            'backUrl' => route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Apresenta à coordenação o formulário para aprovar o tema.
     */
    public function formAprovarCoordenacao(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        return $this->renderFormularioCoordenacao(
            'tenant/pap/decisao-coordenacao/aprovar',
            'grupopap.aprovar',
            'tenant.dashboard.colegio.grupo-pap-aprovacao.aprovar',
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
        );
    }

    /**
     * Apresenta à coordenação o formulário para reprovar o tema.
     */
    public function formReprovarCoordenacao(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        return $this->renderFormularioCoordenacao(
            'tenant/pap/decisao-coordenacao/reprovar',
            'grupopap.reprovar',
            'tenant.dashboard.colegio.grupo-pap-aprovacao.reprovar',
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
        );
    }

    /**
     * Prepara e apresenta um formulário de decisão da coordenação.
     */
    private function renderFormularioCoordenacao(
        string $component,
        string $permission,
        string $submitRoute,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can($permission), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $params = compact('colegio', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');
        $proposta = $this->withExternalGrupo(
            $colegio,
            (string) $cursoTutelado->getKey(),
            (string) $cursoClasse->getKey(),
            (string) $cursoClasseTurno->getKey(),
            (string) $turma->getKey(),
            (string) $grupoPap->getKey(),
            $user,
            function (GrupoPap $grupo, string $actorTenantId): array {
                abort_unless($grupo->podeSerAprovado(), 403);

                return [
                    'grupoPap' => $grupo->only(
                        'id',
                        'nome_grupo',
                        'tema_grupo',
                        'problema',
                        'objectivo_geral',
                        'objectivos_especificos',
                    ),
                    'turma' => $grupo->turma?->only('nome'),
                ];
            },
            $tenantTutorId,
        );

        return Inertia::render($component, [
            ...$proposta,
            'submitUrl' => route($submitRoute, $params),
            'backUrl' => route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Regista a aprovação do tema pela coordenação do colégio.
     */
    public function aprovar(
        AprovarTemaPapRequest $request,
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
        abort_unless($user->can('grupopap.aprovar'), 403);
        $validated = $request->validated();
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->decisaoTemaCoordenacaoService->aprovar(
                $grupo,
                $user,
                $validated['comentario'] ?? null,
                $actorTenantId,
            ),
            $tenantTutorId,
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Erro ao aprovar o tema.']);
        }

        return redirect()->back()->with('success', 'Tema aprovado com sucesso.');
    }

    /**
     * Apresenta ao tutor o formulário para encaminhar o tema à coordenação.
     */
    public function formAprovarTutor(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        $this->authorize('aprovarComoTutor', $grupoPap);
        $params = compact('colegio', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render('tenant/pap/decisao-tutor/enviar-para-coordenacao', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.aprovar-tutor', $params),
            'backUrl' => route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Apresenta ao tutor o formulário para solicitar melhoria do tema.
     */
    public function formSolicitarMelhoriaTutor(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);
        $params = compact('colegio', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render('tenant/pap/decisao-tutor/solicitar-melhoria', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.solicitar-melhoria-tutor', $params),
            'backUrl' => route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Regista a validação do tema pelo tutor e envia-o à coordenação.
     */
    public function aprovarTutor(
        AprovarTemaPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        $this->authorize('aprovarComoTutor', $grupoPap);
        $resultado = $this->decisaoTemaTutorService->enviarParaCoordenacao(
            $grupoPap,
            $user,
            $request->validated()['comentario'] ?? null,
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Este tema não pode ser enviado à coordenação neste momento.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        ))->with('toast', [
            'type' => 'success',
            'message' => 'Tema enviado para a coordenação.',
        ]);
    }

    /**
     * Regista um pedido de melhoria do tema feito pelo tutor.
     */
    public function solicitarMelhoriaComoTutor(
        SolicitarMelhoriaTemaPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);
        $resultado = $this->decisaoTemaTutorService->solicitarMelhoria(
            $grupoPap,
            $user,
            $request->validated()['recomendacao'],
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Este tema não pode receber melhorias neste momento.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        ))->with('toast', [
            'type' => 'warning',
            'message' => 'Melhoria solicitada aos alunos.',
        ]);
    }

    /**
     * Regista a reprovação do tema pela coordenação do colégio.
     */
    public function reprovar(
        ReprovarTemaPapRequest $request,
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
        abort_unless($user->can('grupopap.reprovar'), 403);
        $validated = $request->validated();
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->decisaoTemaCoordenacaoService->reprovar(
                $grupo,
                $user,
                $validated['motivo'],
                $actorTenantId,
            ),
            $tenantTutorId,
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reprovado neste momento.',
            ]);
        }

        return back()->with('success', 'Tema reprovado com sucesso.');
    }

    /**
     * Actualiza um tema PAP depois de uma correcção.
     */
    public function atualizar(ActualizarTemaPapPeloColegioRequest $request, GrupoPap $grupoPap)
    {
        $this->authorize('corrigirTema', $grupoPap);
        $validated = $request->validated();
        $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse');
        $turma = $grupoPap->turma;
        $dadosUnicidade = [
            ...$validated,
            'estudo_caso' => $grupoPap->estudo_caso,
        ];

        $this->temaPapUnicidadeService->validarUnicidade(
            $dadosUnicidade,
            (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
            (string) $turma?->ano_lectivo_id,
            (string) $turma?->curso_classe_turno_id,
            (string) $grupoPap->getKey(),
        );
        $grupoPap->update($validated);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }

    /**
     * Regista um pedido de melhoria do tema pela coordenação do colégio.
     */
    public function solicitarMelhoria(
        SolicitarMelhoriaTemaPapRequest $request,
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
        abort_unless($user->can('grupopap.solicitarMelhoria'), 403);
        $validated = $request->validated();
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->decisaoTemaCoordenacaoService->solicitarMelhoria(
                $grupo,
                $user,
                $validated['recomendacao'],
                $actorTenantId,
            ),
            $tenantTutorId,
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Não é possível solicitar melhoria neste momento.',
            ]);
        }

        return back()->with('success', 'Recomendação de melhoria enviada com sucesso.');
    }

    /**
     * Executa uma operação no grupo após validar e mudar para o tenant de origem.
     *
     * @param  Closure(GrupoPap, string): mixed  $operation
     */
    private function withExternalGrupo(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        User $user,
        Closure $operation,
        string $tenantTutorId,
    ): mixed {
        $vinculo = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();
        $tenantColega = Tenant::query()->findOrFail($vinculo->tenant_tutelado_id);
        $this->crossTenantAccessService->validarAcessoAoGrupoPap(
            $user,
            $tenantColega,
            $grupoPap,
            (string) $vinculo->getKey(),
        );

        return $this->tutelaService->executarNoTenantTutelado(
            $cursoTutelado,
            $tenantTutorId,
            function () use (
                $colegio,
                $cursoTutelado,
                $cursoClasse,
                $cursoClasseTurno,
                $turma,
                $grupoPap,
                $operation,
                $tenantTutorId,
            ): mixed {
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

                return $operation($grupoPapModel, $tenantTutorId);
            },
        );
    }

    /**
     * Reenvia à coordenação o tema corrigido pelo colégio.
     */
    public function reenviar(ReenviarTemaPapPeloColegioRequest $request, GrupoPap $grupoPap)
    {
        /** @var User|null $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user?->hasRole('Secretario do Curso'), 403);
        $this->authorize('reenviarTema', $grupoPap);
        $resultado = $this->reenviarTemaPap->handle(
            $grupoPap,
            Auth::guard('tenant')->user(),
            $request->validated(),
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reenviado neste momento.',
            ]);
        }

        return back()->with('success', 'Tema corrigido e reenviado para nova análise com sucesso.');
    }

    /**
     * Lista os temas que aguardam melhorias no colégio indicado.
     */
    public function melhorias(Instituicao $instituicao)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->instituicao_id === $instituicao->id, 403);
        $temas = GrupoPap::query()
            ->whereIn('status_aprovacao', [
                GrupoPap::APROVACAO_MELHORIA_TUTOR,
                GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
            ])
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                fn ($query) => $query->whereHas(
                    'instituicaoCurso',
                    fn ($instituicaoCursoQuery) => $instituicaoCursoQuery
                        ->where('instituicao_id', $instituicao->id)
                )
            )
            ->with([
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'professor.user',
            ])
            ->latest()
            ->get();

        return inertia('tenant/pap/TemasMelhoria', [
            'temas' => $temas,
            'rotaEditar' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.editar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => ':id',
            ]),
        ]);
    }

    /**
     * Apresenta o formulário para corrigir o tema no colégio.
     */
    public function editar(GrupoPap $grupoPap, Instituicao $instituicao)
    {
        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        return inertia('tenant/pap/EditarTemaMelhoria', [
            'grupoPap' => $grupoPap,
            'rotaAtualizar' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.atualizar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => $grupoPap->id,
            ]),
            'rotaReenviar' => route('tenant.dashboard.colegio.grupo-pap-aprovacao.reenviar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => $grupoPap->id,
            ]),
        ]);
    }

    /**
     * Apresenta o histórico de aprovação do grupo PAP.
     */
    public function historico(GrupoPap $grupoPap)
    {
        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'professor.user',
            'historicoAprovacao.utilizador',
        ]);

        return inertia('tenant/pap/HistoricoAprovacao', [
            'grupoPap' => $grupoPap,
            'historico' => $grupoPap->historicoAprovacao,
        ]);
    }
}
