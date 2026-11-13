<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap;

use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Rules\TemaPapUnico;
use App\Services\Tenant\AprovacaoTemaService;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Tutela\TutelaService;
use App\Traits\NotificaGrupoPap;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AprovacaoTemaPapController extends Controller
{
    use NotificaGrupoPap;

    /**
     * Recebe os serviços de aprovação e de acesso entre tenants.
     */
    public function __construct(
        private AprovacaoTemaService $service,
        private TutelaService $tutelaService,
        private CrossTenantAccessService $crossTenantAccessService,
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

        $temasPendentes = $this->service->temasPendentesParaCoordenador($user);

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
        Request $request,
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
        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->service->aprovar(
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
        Request $request,
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
        $validated = $request->validate([
            'comentario' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update(['status_aprovacao' => GrupoPap::APROVACAO_PENDENTE]);
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::guard('tenant')->id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_PENDENTE,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'comentario' => $validated['comentario'] ?? 'Aprovado pelo professor tutor.',
            ]);
        });
        $this->notificarTemaValidadoPeloTutor(
            $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse.cursoTutelado'),
        );

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
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);
        $validated = $request->validate([
            'recomendacao' => 'required|string|min:10|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'comentario_aprovacao' => $validated['recomendacao'],
            ]);
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::guard('tenant')->id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'comentario' => $validated['recomendacao'],
            ]);
        });

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
        Request $request,
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
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->service->reprovar(
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
    public function atualizar(Request $request, GrupoPap $grupoPap)
    {
        $this->authorize('corrigirTema', $grupoPap);
        $validated = $request->validate([
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
                    (string) $grupoPap->turma?->ano_lectivo_id,
                    (string) $grupoPap->turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                    $request->input('estudo_caso', $grupoPap->estudo_caso),
                ),
            ],
            'problema' => ['nullable', 'string', 'max:2000'],
            'objectivo_geral' => ['nullable', 'string', 'max:2000'],
            'objectivos_especificos' => ['nullable', 'string', 'max:2000'],
        ]);
        $grupoPap->update($validated);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }

    /**
     * Regista um pedido de melhoria do tema pela coordenação do colégio.
     */
    public function solicitarMelhoria(
        Request $request,
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
        $validated = $request->validate([
            'recomendacao' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo, string $actorTenantId) => $this->service->solicitarMelhoria(
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
    public function reenviar(Request $request, GrupoPap $grupoPap)
    {
        /** @var User|null $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user?->hasRole('Secretario do Curso'), 403);
        $this->authorize('reenviarTema', $grupoPap);
        $dados = $request->validate([
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
                    (string) $grupoPap->turma?->ano_lectivo_id,
                    (string) $grupoPap->turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                    $request->input('estudo_caso', $grupoPap->estudo_caso),
                ),
            ],
        ]);
        $resultado = $this->service->reenviar($grupoPap, Auth::guard('tenant')->user(), $dados);

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
