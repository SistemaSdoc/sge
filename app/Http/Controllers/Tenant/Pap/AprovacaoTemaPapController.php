<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use App\Services\Tenant\AprovacaoTemaService;
use App\Traits\NotificaGrupoPap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AprovacaoTemaPapController extends Controller
{
    use NotificaGrupoPap;

    /**
     * Recebe o serviço de decisões e transições da proposta PAP.
     */
    public function __construct(private AprovacaoTemaService $service) {}

    /**
     * Impede decisões PAP por utilizadores com funções de direcção.
     */
    private function autorizarDecisaoPap(): User
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        return $user;
    }

    /**
     * Lista os temas pendentes de aprovação pelo coordenador.
     */
    public function pendentes()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        if ($user->hasAnyRole(['Director', 'Subdirector']) || ! $user->professor) {
            return inertia('tenant/pap/PendentesAprovacao', [
                'temasPendentes' => [],
                'rotaAprovar' => null,
                'rotaReprovar' => null,
                'rotaMelhoria' => null,
            ]);
        }

        $temasPendentes = $this->service->temasPendentesParaCoordenador($user->professor->id);

        return inertia('tenant/pap/PendentesAprovacao', [
            'temasPendentes' => $temasPendentes,
            'rotaAprovar' => route('tenant.dashboard.grupo-pap-aprovacao.aprovar', ':id'),
            'rotaReprovar' => route('tenant.dashboard.grupo-pap-aprovacao.reprovar', ':id'),
            'rotaMelhoria' => route('tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria', ':id'),
        ]);
    }

    /**
     * Apresenta o formulário para solicitar melhoria pela coordenação.
     */
    public function formSolicitarMelhoriaCoordenacao(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('solicitarMelhoria', $grupoPap);
        $params = compact('instituicao', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render('tenant/pap/decisao-coordenacao/solicitar-melhoria', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivo_geral', 'objectivos_especificos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route('tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria', [
                'grupoPap' => $grupoPap->id,
            ]),
            'backUrl' => route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Apresenta o formulário de aprovação pela coordenação.
     */
    public function formAprovarCoordenacao(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('aprovar', $grupoPap);

        return $this->renderFormularioCoordenacao(
            'tenant/pap/decisao-coordenacao/aprovar',
            $instituicao,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            'tenant.dashboard.grupo-pap-aprovacao.aprovar',
        );
    }

    /**
     * Apresenta o formulário de reprovação pela coordenação.
     */
    public function formReprovarCoordenacao(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('reprovar', $grupoPap);

        return $this->renderFormularioCoordenacao(
            'tenant/pap/decisao-coordenacao/reprovar',
            $instituicao,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            'tenant.dashboard.grupo-pap-aprovacao.reprovar',
        );
    }

    /**
     * Prepara o payload comum aos formulários da coordenação.
     */
    private function renderFormularioCoordenacao(
        string $component,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        string $submitRoute,
    ): Response {
        $params = compact('instituicao', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render($component, [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivo_geral', 'objectivos_especificos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route($submitRoute, ['grupoPap' => $grupoPap->id]),
            'backUrl' => route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Apresenta ao tutor o formulário para encaminhar o tema à coordenação.
     */
    public function formAprovarTutor(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('aprovarComoTutor', $grupoPap);
        $params = compact('instituicao', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render('tenant/pap/decisao-tutor/enviar-para-coordenacao', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivo_geral', 'objectivos_especificos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route('tenant.dashboard.grupo-pap-aprovacao.aprovar-tutor', $params),
            'backUrl' => route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Apresenta ao tutor o formulário para solicitar melhoria do tema.
     */
    public function formSolicitarMelhoriaTutor(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('solicitarMelhoriaComoTutor', $grupoPap);
        $params = compact('instituicao', 'cursoTutelado', 'cursoClasse', 'cursoClasseTurno', 'turma', 'grupoPap');

        return Inertia::render('tenant/pap/decisao-tutor/solicitar-melhoria', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivo_geral', 'objectivos_especificos'),
            'turma' => $turma->only('nome'),
            'submitUrl' => route('tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria-tutor', $params),
            'backUrl' => route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params),
        ]);
    }

    /**
     * Regista a validação do tema pelo tutor e envia-o à coordenação.
     */
    public function aprovarTutor(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('aprovarComoTutor', $grupoPap);
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

        $this->notificarTemaValidadoPeloTutor($grupoPap);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', compact(
            'instituicao',
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
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('solicitarMelhoriaComoTutor', $grupoPap);
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

        $grupoPap->load('alunos.user');
        $this->notificarMelhoriasSolicitadas($grupoPap, 'tutor');

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', compact(
            'instituicao',
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
     * Regista a aprovação do tema pela coordenação.
     */
    public function aprovar(Request $request, GrupoPap $grupoPap)
    {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('aprovar', $grupoPap);

        if (! $grupoPap->podeSerAprovado()) {
            return back()->withErrors([
                'grupo' => 'Este tema já foi finalizado e não pode ser alterado.',
            ])->with('status', 'erro');
        }

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);
        $resultado = $this->service->aprovar($grupoPap, $user, $validated['comentario'] ?? null);

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Erro ao aprovar o tema.']);
        }

        return redirect()->back()->with('success', 'Tema aprovado com sucesso.');
    }

    /**
     * Regista a reprovação do tema pela coordenação.
     */
    public function reprovar(Request $request, GrupoPap $grupoPap)
    {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('reprovar', $grupoPap);
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $resultado = $this->service->reprovar($grupoPap, $user, $validated['motivo']);

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Este tema não pode ser reprovado neste momento.']);
        }

        return back()->with('success', 'Tema reprovado com sucesso.');
    }

    /**
     * Actualiza os dados do tema depois de uma melhoria solicitada.
     */
    public function atualizar(Request $request, GrupoPap $grupoPap)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('atualizarTema', $grupoPap);
        $validated = $request->validate([
            'nome_grupo' => 'required|string|max:500',
            'tema_grupo' => [
                'required',
                'string',
                'max:500',
                new TemaPapUnico(
                    (string) $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
                    (string) $grupoPap->turma?->ano_lectivo_id,
                    (string) $grupoPap->turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                ),
            ],
            'problema' => 'nullable|string|max:2000',
            'objectivo_geral' => 'nullable|string|max:2000',
            'objectivos_especificos' => 'nullable|string|max:2000',
            'estudo_caso' => [
                'nullable',
                'string',
                'max:2000',
                new EstudoCasoPapUnico(
                    (string) $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
                    (string) $grupoPap->turma?->ano_lectivo_id,
                    (string) $grupoPap->turma?->curso_classe_turno_id,
                    $request->input('tema_grupo', $grupoPap->tema_grupo),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ]);
        $grupoPap->update($validated);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }

    /**
     * Regista um pedido de melhoria do tema pela coordenação.
     */
    public function solicitarMelhoria(Request $request, GrupoPap $grupoPap)
    {
        $user = $this->autorizarDecisaoPap();
        Gate::forUser($user)->authorize('solicitarMelhoria', $grupoPap);
        $validated = $request->validate([
            'recomendacao' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $resultado = $this->service->solicitarMelhoria($grupoPap, $user, $validated['recomendacao']);

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Não é possível solicitar melhoria neste momento.']);
        }

        return back()->with('success', 'Recomendação de melhoria enviada com sucesso.');
    }

    /**
     * Reenvia à coordenação um tema corrigido pelo colégio.
     */
    public function reenviar(Request $request, GrupoPap $grupoPap)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('reenviarTema', $grupoPap);

        if (! $grupoPap->podeSerReenviado()) {
            return back()->withErrors(['grupo' => 'Este tema não pode ser reenviado neste momento.']);
        }

        $validated = $request->validate([
            'nome_grupo' => 'required|string|max:500',
            'tema_grupo' => [
                'required',
                'string',
                'max:500',
                new TemaPapUnico(
                    (string) $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
                    (string) $grupoPap->turma?->ano_lectivo_id,
                    (string) $grupoPap->turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                ),
            ],
            'problema' => 'nullable|string|max:2000',
            'objectivo_geral' => 'nullable|string|max:2000',
            'objectivos_especificos' => 'nullable|string|max:2000',
        ]);
        $resultado = $this->service->reenviar($grupoPap, Auth::guard('tenant')->user(), $validated);

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Erro ao reenviar o tema.']);
        }

        $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');
        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno->cursoClasse;
        $cursoTutelado = $cursoClasse->cursoTutelado;

        return redirect()->route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $cursoTutelado->instituicaoCurso->instituicao_id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('success', 'Tema reenviado com sucesso.');
    }

    /**
     * Lista os temas PAP que aguardam melhorias no colégio.
     */
    public function melhorias()
    {
        $user = Auth::guard('tenant')->user();
        Gate::forUser($user)->authorize('viewMelhorias', GrupoPap::class);
        $temas = GrupoPap::query()
            ->whereIn('status_aprovacao', [
                GrupoPap::APROVACAO_MELHORIA_TUTOR,
                GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
            ])
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                fn ($query) => $query->whereHas(
                    'instituicaoCurso',
                    fn ($institutionQuery) => $institutionQuery->where('instituicao_id', $user->instituicao_id)
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
            'rotaEditar' => route('tenant.dashboard.grupo-pap-aprovacao.editar', ':id'),
        ]);
    }

    /**
     * Apresenta o formulário para corrigir um tema com melhorias pedidas.
     */
    public function editar(GrupoPap $grupoPap)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('atualizarTema', $grupoPap);
        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        return inertia('tenant/pap/EditarTemaMelhoria', [
            'grupoPap' => $grupoPap,
            'rotaAtualizar' => route('tenant.dashboard.grupo-pap-aprovacao.atualizar', ':id'),
            'rotaReenviar' => route('tenant.dashboard.grupo-pap-aprovacao.reenviar', ':id'),
        ]);
    }

    /**
     * Apresenta o histórico de aprovação do grupo PAP.
     */
    public function historico(GrupoPap $grupoPap)
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('viewHistorico', $grupoPap);
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
