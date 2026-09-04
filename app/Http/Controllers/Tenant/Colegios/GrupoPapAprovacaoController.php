<?php

namespace App\Http\Controllers\Tenant\Colegios;

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
use App\Services\Tenant\AprovacaoTemaService;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Tutela\TutelaService;
use App\Traits\NotificaGrupoPap;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * IMPORTANTE: a permissão do tutor é validada antes de Tenant::run().
 * Dentro do tenant do colégio, a autorização local não representa o tutor.
 */
class GrupoPapAprovacaoController extends Controller
{
    use NotificaGrupoPap;

    public function __construct(
        private AprovacaoTemaService $service,
        private TutelaService $tutelaService,
        private CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Listar temas PAP pendentes de aprovação
     * para o coordenador do curso.
     */
    public function pendentes(Instituicao $colegio)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless($user->can('grupopap.aprovar'), 403);

        // O utilizador precisa estar associado a um professor
        if (! $user->professor) {
            return inertia('tenant/pap/PendentesAprovacao', [
                'temasPendentes' => [],
                'rotaAprovar' => null,
                'rotaReprovar' => null,
                'rotaMelhoria' => null,
            ]);
        }

        // Buscar apenas os temas dos cursos
        // onde o professor é coordenador.
        $temasPendentes = $this->service->temasPendentesParaCoordenador(
            $user->professor->id
        );

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
     * Aprovar tema PAP
     */
    public function aprovar(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

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
            return back()->withErrors([
                'grupo' => 'Erro ao aprovar o tema.',
            ]);
        }

        // ✅ FORÇA RELOAD COMPLETO
        return redirect()->back()->with(
            'success',
            'Tema aprovado com sucesso.'
        );
    }

    public function aprovarTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('aprovarComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_PENDENTE,
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::guard('tenant')->id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_PENDENTE,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivos' => $grupoPap->objectivos,
                'comentario' => $validated['comentario'] ?? 'Aprovado pelo professor tutor.',
            ]);
        });

        $this->notificarTemaValidadoPeloTutor(
            $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse.cursoTutelado'),
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Tema enviado para a coordenação.',
        ]);
    }

    public function solicitarMelhoriaComoTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
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
                'objectivos' => $grupoPap->objectivos,
                'comentario' => $validated['recomendacao'],
            ]);
        });

        return back()->with('toast', [
            'type' => 'warning',
            'message' => 'Melhoria solicitada aos alunos.',
        ]);
    }

    /**
     * Reprovar tema PAP
     */
    public function reprovar(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless($user->can('grupopap.reprovar'), 403);

        // O motivo da reprovação é obrigatório
        $validated = $request->validate([
            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();

        // Executar reprovação
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

        // Verificar se o tema pode ser reprovado
        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reprovado neste momento.',
            ]);
        }

        return back()->with(
            'success',
            'Tema reprovado com sucesso.'
        );
    }

    /**
     * Atualizar tema PAP após correção
     */
    public function atualizar(
        Request $request,
        GrupoPap $grupoPap
    ) {
        $this->authorize('corrigirTema', $grupoPap);

        $validated = $request->validate([
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => ['required', 'string', 'max:255'],
            'problema' => ['nullable', 'string', 'max:2000'],
            'objectivos' => ['nullable', 'string', 'max:2000'],
        ]);

        $grupoPap->update($validated);

        return back()->with(
            'success',
            'Tema atualizado com sucesso.'
        );
    }

    /**
     * Solicitar melhoria no tema PAP
     */
    public function solicitarMelhoria(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless($user->can('grupopap.solicitarMelhoria'), 403);

        // A recomendação é obrigatória
        $validated = $request->validate([
            'recomendacao' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();

        // Executar solicitação de melhoria
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

        // Verificar se a operação foi realizada
        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Não é possível solicitar melhoria neste momento.',
            ]);
        }

        return back()->with(
            'success',
            'Recomendação de melhoria enviada com sucesso.'
        );
    }

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
            function () use ($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap, $operation, $tenantTutorId): mixed {
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
     * Reenviar tema PAP após correção solicitada.
     *
     * Esta ação é realizada pelo colégio.
     */
    public function reenviar(
        Request $request,
        GrupoPap $grupoPap
    ) {
        // $this->authorize('reenviarTema', $grupoPap);

        $dados = $request->validate([
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => ['required', 'string', 'max:255'],
        ]);

        $resultado = $this->service->reenviar(
            $grupoPap,
            Auth::guard('tenant')->user(),
            $dados
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reenviado neste momento.',
            ]);
        }

        return back()->with('success', 'Tema corrigido e reenviado para nova análise com sucesso.');
    }

    /**
     * Temas que precisam de melhoria
     *
     * Página do colégio
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
     * Formulário para corrigir tema
     */
    public function editar(GrupoPap $grupoPap, Instituicao $instituicao)
    {
        /* $this->authorize(
             'editarTema',
             $grupoPap
         );*/

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
     * Histórico de aprovação do grupo PAP
     */
    public function historico(GrupoPap $grupoPap)
    {
        /* $this->authorize(
             'verHistorico',
             $grupoPap
         );*/

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
