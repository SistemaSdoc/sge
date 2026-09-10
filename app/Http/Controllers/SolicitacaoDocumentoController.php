<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitacaoDocumentoRequest;
use App\Models\SolicitacaoDocumento;
use App\Services\ElegibilidadeDocumentoService;
use App\Services\Rupe\RupeGeneratorInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SolicitacaoDocumentoController extends Controller
{
    /**
     * Página do aluno – lista as suas solicitações.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $aluno = optional($user)->aluno;
        $turmaAtual = $aluno?->turmaActual()->first() ?? $aluno?->turmas()->orderByDesc('created_at')->first();
        $cursoTuteladoAtual = $aluno?->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        $cursoAtual = $cursoTuteladoAtual?->instituicaoCurso?->curso;
        $classeAtual = $turmaAtual?->cursoClasseTurno?->cursoClasse?->classe;
        $anoLectivoAtual = $turmaAtual?->anoLectivo ?? $aluno?->inscricao?->anoLectivo;

        $classeAtual = $classeAtual ?? ($aluno?->turmaActual()->first()?->classe ?? null);

        // Um curso só é "realmente tutelado" quando a instituição responsável
        // é de facto do tipo 'instituto'. Por defeito, ao criar um curso
        // tutelado, o sistema preenche instituicao_tutora_id com a própria
        // instituição (o colégio) até alguém a editar manualmente — isso NÃO
        // conta como tutela real e não deve liberar o pedido de certificado.
        $temTutelaInstituto = $cursoTuteladoAtual?->instituicaoTutora?->tipo === 'instituto';
        $podeCertificado = ($classeAtual?->emite_certificado ?? false) && $temTutelaInstituto;

        $tipos = config('documentos.types_base', []);

        if ($podeCertificado) {
            $tipos[] = config('documentos.certificado');
        }

        $solicitacoes = $aluno
            ? $aluno->solicitacoesDocumentos()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn(SolicitacaoDocumento $solicitacao) => [
                    'id' => $solicitacao->id,
                    'tipo_documento' => $solicitacao->tipo_documento,
                    'tipo_label' => $solicitacao->tipoLabel,
                    'motivo' => $solicitacao->motivo,
                    'status' => $solicitacao->status,
                    'observacoes' => $solicitacao->observacoes,
                    'responsavel_instituicao_id' => $solicitacao->instituicaoResponsavelId(),
                    'instituicao_tutora_id' => $solicitacao->instituicao_tutora_id,
                    'estado_pagamento' => $solicitacao->estado_pagamento ?? 'pendente',
                    'data_pagamento_confirmado' => $solicitacao->data_pagamento_confirmado?->format('d/m/Y H:i'),
                    'data_emissao' => $solicitacao->data_emissao?->format('d/m/Y H:i'),
                    'documento_gerado' => (bool) $solicitacao->data_emissao,
                    'data_levantamento' => $solicitacao->data_levantamento?->format('d/m/Y H:i'),
                    'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                    'rupe_referencia' => $solicitacao->rupe_referencia,
                    'rupe_entidade' => $solicitacao->rupe_entidade,
                    'rupe_valor' => $solicitacao->rupe_valor,
                    'can_decidir' => $this->decidir($solicitacao),
                    'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                    'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                    'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                    'can_delete' => $this->deletePermission($solicitacao),
                ])
            : [];

        $elegibilidade = new ElegibilidadeDocumentoService;
        $classesDisponiveis = $aluno ? $elegibilidade->classesDisponiveisParaDeclaracao($aluno) : [];

        return Inertia::render('dashboards/aluno/solicitacoes-documentos/index', [
            'solicitacoes' => $solicitacoes,
            'tipos' => $tipos,
            'classes_disponiveis' => $classesDisponiveis,
            'pode_certificado' => $podeCertificado,
            'curso_atual' => $cursoAtual ? ['id' => $cursoAtual->id, 'nome' => $cursoAtual->nome] : null,
            'turma_atual' => $turmaAtual ? ['id' => $turmaAtual->id, 'nome' => $turmaAtual->nome] : null,
            'classe_atual' => $classeAtual ? ['id' => $classeAtual->id, 'nome' => $classeAtual->nome] : null,
            'ano_lectivo_atual' => $anoLectivoAtual ? ['id' => $anoLectivoAtual->id, 'nome' => $anoLectivoAtual->nome] : null,
            'user' => ['id' => $user?->id, 'nome' => $user?->nome],
        ]);
    }

    /**
     * Cria uma nova solicitação (aluno).
     */
    public function store(StoreSolicitacaoDocumentoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $aluno = $request->user()?->aluno;

        if (! $aluno) {
            return back()->withErrors(['aluno' => 'Não foi possível identificar o aluno autenticado.']);
        }

        $turmaAtual = $aluno->turmaActual()->first() ?? $aluno->turmas()->orderByDesc('created_at')->first();
        $cursoTutelado = $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        $cursoAtual = $cursoTutelado?->instituicaoCurso?->curso;
        $classeAtual = $turmaAtual?->cursoClasseTurno?->cursoClasse?->classe;
        $anoLectivoAtual = $turmaAtual?->anoLectivo ?? $aluno->inscricao?->anoLectivo;

        $validated['curso_id'] ??= $cursoAtual?->id;
        $validated['turma_id'] ??= $turmaAtual?->id;
        $validated['classe_id'] ??= $classeAtual?->id;
        $validated['ano_lectivo_id'] ??= $anoLectivoAtual?->id;

        // A tutora só é considerada "real" quando a instituição responsável
        // pelo curso tutelado é de facto do tipo 'instituto'. Ver nota em index().
        $temTutelaInstitutoReal = $cursoTutelado?->instituicaoTutora?->tipo === 'instituto';
        $instituicaoTutoraCurso = $temTutelaInstitutoReal ? $cursoTutelado->instituicao_tutora_id : null;

        if (($validated['tipo_documento'] ?? null) === 'certificado') {
            if (! $classeAtual || ! ($classeAtual->emite_certificado ?? false)) {
                abort(422, 'A classe atual do aluno não permite solicitar certificado.');
            }

            if (! $instituicaoTutoraCurso) {
                abort(422, 'O curso do aluno não está associado a uma instituição tutora (instituto) responsável pela emissão de certificados.');
            }
        }

        $instituicaoOrigem = $aluno->instituicao_id ?? $request->user()?->instituicao_id;
        $instituicaoEmissora = $validated['instituicao_emissora_id'] ?? $instituicaoOrigem;

        // Para certificado usamos apenas a tutora real (instituto). Para os
        // demais tipos, guarda a tutora do curso tutelado se existir (apenas
        // como referência/relatório — não afeta quem decide o pedido), sem
        // nunca cair por defeito na própria instituição de origem.
        $instituicaoTutora = $validated['tipo_documento'] === 'certificado'
            ? $instituicaoTutoraCurso
            : $cursoTutelado?->instituicao_tutora_id;

        $solicitacao = SolicitacaoDocumento::create([
            'aluno_id' => $aluno->id,
            'curso_id' => $validated['curso_id'] ?? null,
            'turma_id' => $validated['turma_id'] ?? null,
            'classe_id' => $validated['classe_id'] ?? null,
            'ano_lectivo_id' => $validated['ano_lectivo_id'] ?? null,
            'instituicao_origem_id' => $instituicaoOrigem,
            'instituicao_tutora_id' => $instituicaoTutora,
            'instituicao_aprovadora_id' => null, // só será preenchido na aprovação
            'instituicao_emissora_id' => $instituicaoEmissora,
            'tipo_documento' => $validated['tipo_documento'],
            'motivo' => $validated['motivo'],
            'observacoes' => $validated['observacoes'] ?? null,
            'status' => 'pendente',
            'numero_processo' => SolicitacaoDocumento::gerarNumeroProcesso($instituicaoEmissora),
            'data_solicitacao' => now(),
        ]);

        $geradorRupe = app(RupeGeneratorInterface::class);
        try {
            $rupe = $geradorRupe->gerar($solicitacao);
        } catch (\Throwable $e) {
            $rupe = ['referencia' => null, 'entidade' => null, 'valor' => null, 'gerado_em' => null];
        }
        $solicitacao->marcarRupeGerado($rupe, false);

        return redirect()->route('solicitacoes-documentos.index')
            ->with('success', 'Solicitação de ' . $solicitacao->tipoLabel . ' registada com sucesso. Aguarde a análise da sua solicitação.');
    }

    /**
     * Página do colégio – lista solicitações cuja origem é o próprio colégio.
     * Apenas utilizadores com instituição do tipo 'colegio' podem aceder.
     */
    public function colegioIndex(): Response
    {
        $user = Auth::user();

        if ((optional($user)->hasRole('Aluno') ?? false) || (optional($user)->hasRole('Candidato') ?? false)) {
            abort(403, 'Sem permissão para aceder a esta área.');
        }

        $instituicao = $user?->instituicao;
        if (! $instituicao || $instituicao->tipo !== 'colegio') {
            abort(403, 'Acesso apenas para colégios.');
        }

        $instituicaoId = $instituicao->id;

        $solicitacoes = SolicitacaoDocumento::query()
            ->where('instituicao_origem_id', $instituicaoId)
            ->whereIn('status', ['pendente', 'aprovado', 'pago', 'pronto', 'entregue', 'rejeitado'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->numero_processo,
                'motivo' => $solicitacao->motivo,
                'observacoes' => $solicitacao->observacoes,
                'status' => $solicitacao->status,
                'responsavel_instituicao_id' => $solicitacao->instituicaoResponsavelId(),
                'instituicao_tutora_id' => $solicitacao->instituicao_tutora_id,
                'estado_pagamento' => $solicitacao->estado_pagamento ?? 'pendente',
                'data_pagamento_confirmado' => $solicitacao->data_pagamento_confirmado?->format('d/m/Y H:i'),
                'data_emissao' => $solicitacao->data_emissao?->format('d/m/Y H:i'),
                'documento_gerado' => (bool) $solicitacao->data_emissao,
                'data_levantamento' => $solicitacao->data_levantamento?->format('d/m/Y H:i'),
                'encaminhado_para_tutela' => (bool) $solicitacao->data_aprovacao,
                'encaminhado_em' => $solicitacao->data_aprovacao?->format('d/m/Y H:i'),
                'aluno' => $solicitacao->aluno?->user?->nome,
                'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                'rupe_referencia' => $solicitacao->rupe_referencia,
                'rupe_entidade' => $solicitacao->rupe_entidade,
                'rupe_valor' => $solicitacao->rupe_valor,
                'can_decidir' => $this->decidir($solicitacao),
                'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                'can_delete' => $this->deletePermission($solicitacao),
            ]);

        return Inertia::render('dashboards/colegio/solicitacoes-documentos/index', [
            'solicitacoes' => $solicitacoes,
            'instituicao_id' => $instituicaoId,
        ]);
    }

    /**
     * Dashboard da tutela (resumo).
     */
    public function tutelaDashboard(): Response
    {
        $user = Auth::user();

        $solicitacoes = SolicitacaoDocumento::query()
            ->where('instituicao_tutora_id', $user?->instituicao_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('dashboards/tutela/index', [
            'total' => $solicitacoes->count(),
            'pendentes' => $solicitacoes->where('status', 'pendente')->count(),
            'aprovadas' => $solicitacoes->where('status', 'aprovado')->count(),
            'rejeitadas' => $solicitacoes->where('status', 'rejeitado')->count(),
            'instituicao_id' => $user?->instituicao_id,
        ]);
    }

    /**
     * Página da tutela – lista solicitações que a tutela tutela.
     * Apenas utilizadores com instituição do tipo 'instituto' podem aceder.
     * Distingue entre solicitações locais (origem = tutora) e tuteladas (origem != tutora).
     */
    public function tutelaIndex(): Response
    {
        $user = Auth::user();

        if ((optional($user)->hasRole('Aluno') ?? false) || (optional($user)->hasRole('Candidato') ?? false)) {
            abort(403, 'Sem permissão para aceder a esta área.');
        }

        $instituicao = $user?->instituicao;
        if (! $instituicao || $instituicao->tipo !== 'instituto') {
            abort(403, 'Acesso apenas para institutos.');
        }

        $instituicaoId = $instituicao->id;

        $solicitacoesLocais = SolicitacaoDocumento::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->where('instituicao_origem_id', $instituicaoId)
            ->whereIn('status', ['pendente', 'aprovado', 'pago', 'pronto', 'entregue', 'rejeitado'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->numero_processo,
                'motivo' => $solicitacao->motivo,
                'observacoes' => $solicitacao->observacoes,
                'status' => $solicitacao->status,
                'responsavel_instituicao_id' => $solicitacao->instituicaoResponsavelId(),
                'instituicao_tutora_id' => $solicitacao->instituicao_tutora_id,
                'estado_pagamento' => $solicitacao->estado_pagamento ?? 'pendente',
                'data_pagamento_confirmado' => $solicitacao->data_pagamento_confirmado?->format('d/m/Y H:i'),
                'data_emissao' => $solicitacao->data_emissao?->format('d/m/Y H:i'),
                'documento_gerado' => (bool) $solicitacao->data_emissao,
                'data_levantamento' => $solicitacao->data_levantamento?->format('d/m/Y H:i'),
                'encaminhado_para_tutela' => (bool) $solicitacao->data_aprovacao,
                'aluno' => $solicitacao->aluno?->user?->nome,
                'origem' => $solicitacao->instituicaoOrigem?->nome ?? 'Instituição',
                'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                'can_decidir' => $this->decidir($solicitacao),
                'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                'can_delete' => $this->deletePermission($solicitacao),
            ]);

        $solicitacoesTuteladas = SolicitacaoDocumento::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->where('instituicao_origem_id', '!=', $instituicaoId)
            ->whereIn('status', ['pendente', 'aprovado', 'pago', 'pronto', 'entregue', 'rejeitado'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->numero_processo,
                'motivo' => $solicitacao->motivo,
                'observacoes' => $solicitacao->observacoes,
                'status' => $solicitacao->status,
                'responsavel_instituicao_id' => $solicitacao->instituicaoResponsavelId(),
                'instituicao_tutora_id' => $solicitacao->instituicao_tutora_id,
                'estado_pagamento' => $solicitacao->estado_pagamento ?? 'pendente',
                'data_pagamento_confirmado' => $solicitacao->data_pagamento_confirmado?->format('d/m/Y H:i'),
                'data_emissao' => $solicitacao->data_emissao?->format('d/m/Y H:i'),
                'documento_gerado' => (bool) $solicitacao->data_emissao,
                'data_levantamento' => $solicitacao->data_levantamento?->format('d/m/Y H:i'),
                'encaminhado_para_tutela' => (bool) $solicitacao->data_aprovacao,
                'aluno' => $solicitacao->aluno?->user?->nome,
                'origem' => $solicitacao->instituicaoOrigem?->nome ?? 'Colégio',
                'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                'can_decidir' => $this->decidir($solicitacao),
                'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                'can_delete' => $this->deletePermission($solicitacao),
            ]);

        return Inertia::render('dashboards/tutela/solicitacoes-documentos/index', [
            'solicitacoes_locais' => $solicitacoesLocais,
            'solicitacoes_tuteladas' => $solicitacoesTuteladas,
            'instituicao_id' => $instituicaoId,
        ]);
    }
    /**
     * Envia um pedido pendente para a tutela (apenas para colégios).
     */
    public function enviarParaTutela(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        $user = Auth::user();

        if (! $user?->instituicao_id || $user->instituicao_id !== $solicitacao->instituicao_origem_id) {
            abort(403, 'Sem permissão para encaminhar este pedido.');
        }

        if ($solicitacao->status !== 'pendente') {
            abort(422, 'Só é possível encaminhar pedidos pendentes.');
        }

        // Regista o encaminhamento (não aprova automaticamente)
        $solicitacao->status = 'pendente'; // mantém pendente
        $solicitacao->instituicao_aprovadora_id = $user->instituicao_id; // quem encaminhou
        $solicitacao->data_aprovacao = now(); // regista a data de encaminhamento
        $solicitacao->save();

        return back()->with('success', 'Pedido de ' . $solicitacao->tipoLabel . ' encaminhado para a instituição tutora para análise.');
    }

    // -----------------------------------------------------------------------
    // MÉTODOS DE AUTORIZAÇÃO (usados para verificar permissões no frontend)
    // -----------------------------------------------------------------------

    public function decidir(SolicitacaoDocumento $solicitacao): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        if ($user->hasRole('SuperAdmin')) return true;

        if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
            return false;
        }

        if ($solicitacao->tipo_documento === 'certificado') {
            return $user->instituicao?->tipo === 'instituto'
                && $user->instituicao_id === $solicitacao->instituicao_tutora_id;
        }

        return $user->instituicao_id === $solicitacao->instituicao_origem_id;
    }

    public function marcarComoPago(SolicitacaoDocumento $solicitacao): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        if ($user->hasRole('SuperAdmin')) return true;

        if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function marcarComoPronto(SolicitacaoDocumento $solicitacao): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        if ($user->hasRole('SuperAdmin')) return true;

        if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function marcarComoLevantadoPermission(SolicitacaoDocumento $solicitacao): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        if ($user->hasRole('SuperAdmin')) return true;

        if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function deletePermission(SolicitacaoDocumento $solicitacao): bool
{
    $user = Auth::user();
    if (! $user) return false;

    $entregue = $solicitacao->status === SolicitacaoDocumento::STATUS_ENTREGUE
        || (bool) $solicitacao->data_levantamento;

    if ($user->hasRole('SuperAdmin')) {
        return true;
    }

    // Aluno só pode apagar o próprio pedido, e só depois de entregue
    if ($user->hasRole('Aluno')) {
        return $entregue
            && $solicitacao->aluno
            && $user->id === $solicitacao->aluno->user_id;
    }

    if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
        return false;
    }

    return $entregue && $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
}

    // -----------------------------------------------------------------------
    // AÇÕES (processam as operações)
    // -----------------------------------------------------------------------

    /**
     * Processa a decisão de aprovação/rejeição (apenas Director/Secretaria).
     */
    public function processarDecisao(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->decidir($solicitacao)) {
            abort(403, 'Sem permissão para decidir este pedido.');
        }

        $request->validate([
            'decisao' => 'required|in:aprovado,rejeitado',
        ]);

        if ($request->decisao === 'aprovado') {
            $solicitacao->aprovar(
                instituicaoAprovadoraId: Auth::user()->instituicao_id,
                observacoes: $request->input('observacoes')
            );
            $mensagem = 'Pedido aprovado com sucesso.';
        } else {
            $solicitacao->rejeitar();
            $mensagem = 'Pedido rejeitado.';
        }

        return back()->with('success', $mensagem);
    }

    /**
     * Marca a solicitação como paga (Director/Secretaria).
     */
    public function marcarComoPagoAction(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->marcarComoPago($solicitacao)) {
            abort(403, 'Sem permissão para marcar como pago.');
        }

        if ($solicitacao->status !== 'aprovado') {
            abort(422, 'Só é possível marcar como pago um pedido aprovado.');
        }

        if ($solicitacao->estado_pagamento === 'pago') {
            return back()->with('info', 'Este documento já foi marcado como pago.');
        }

        $solicitacao->marcarComoPago();

        return back()->with('success', 'Pagamento registado com sucesso.');
    }

    /**
     * Emite o documento (marca como pronto) – Director/Secretaria.
     */
    public function emitir(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        $validated = $request->validate([
            'numero_registro_tutora' => 'required|string|max:255',
        ]);

        if (! $this->marcarComoPronto($solicitacao)) {
            abort(403, 'Sem permissão para marcar como pronto.');
        }

        // Verificações do fluxo
        if (! in_array($solicitacao->status, ['aprovado', 'pago'], true)) {
            abort(422, 'Só é possível emitir documentos aprovados e pagos.');
        }

        if ($solicitacao->estado_pagamento !== 'pago') {
            abort(422, 'Pagamento não confirmado.');
        }

        if ($solicitacao->tipo_documento !== 'certificado') {
            if ($solicitacao->instituicao_aprovadora_id !== $solicitacao->instituicao_origem_id) {
                abort(422, 'Este pedido deve ser aprovado pela instituição de origem.');
            }
        }

        if ($solicitacao->data_emissao) {
            abort(422, 'Este documento já foi marcado como pronto.');
        }

        if ($solicitacao->tipo_documento === 'certificado') {
            if (! $solicitacao->instituicao_tutora_id || $solicitacao->instituicao_aprovadora_id !== $solicitacao->instituicao_tutora_id) {
                abort(422, 'Só é possível emitir certificados aprovados pela tutela.');
            }
        }

        $solicitacao->emitir($validated['numero_registro_tutora']);

        return back()->with('success', 'O ' . $solicitacao->tipoLabel . ' encontra-se pronto para levantamento. Dirija-se à secretaria para o levantar.');
    }

    /**
     * Marca o documento como levantado (Director/Secretaria).
     */
    public function marcarComoLevantado(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->marcarComoLevantadoPermission($solicitacao)) {
            abort(403, 'Sem permissão para marcar como levantado.');
        }

        if (! $solicitacao->data_emissao && $solicitacao->status !== 'pronto') {
            abort(422, 'Só é possível registar o levantamento depois de o documento estar pronto.');
        }

        if ($solicitacao->data_levantamento || $solicitacao->status === 'entregue') {
            return back()->with('info', 'Este ' . $solicitacao->tipoLabel . ' já foi levantado.');
        }

        $solicitacao->marcarComoLevantado(Auth::user());

        return back()->with('success', 'Levantamento do ' . $solicitacao->tipoLabel . ' registado com sucesso.');
    }

    /**
     * Elimina a solicitação (apenas após entregue e com permissão).
     */
    public function destroy(SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->deletePermission($solicitacao)) {
            abort(403, 'Sem permissão para eliminar.');
        }

        $solicitacao->delete();

        return back()->with('success', 'Pedido apagado com sucesso.');
    }

    // -----------------------------------------------------------------------
    // PÁGINAS DE EMISSÃO (não confundir com a ação emitir)
    // -----------------------------------------------------------------------

    public function emissaoDashboard(): Response
    {
        $user = Auth::user();

        $solicitacoes = SolicitacaoDocumento::query()
            ->where('instituicao_emissora_id', $user?->instituicao_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('dashboards/emissao/index', [
            'total' => $solicitacoes->count(),
            'aprovadas' => $solicitacoes->whereIn('status', ['aprovado', 'pago'])->whereNull('data_emissao')->count(),
            'emitidas' => $solicitacoes->whereNotNull('data_emissao')->count(),
            'pendentes' => $solicitacoes->where('status', 'pendente')->count(),
            'pagas' => $solicitacoes->where('status', 'pago')->count(),
            'prontas' => $solicitacoes->where('status', 'pronto')->count(),
            'entregues' => $solicitacoes->where('status', 'entregue')->count(),
            'instituicao_id' => $user?->instituicao_id,
        ]);
    }

    public function emissaoIndex(): Response
    {
        $user = Auth::user();

        if ((optional($user)->hasRole('Aluno') ?? false) || (optional($user)->hasRole('Candidato') ?? false)) {
            abort(403, 'Sem permissão para aceder a esta área.');
        }

        $solicitacoes = SolicitacaoDocumento::query()
            ->where('instituicao_emissora_id', $user?->instituicao_id)
            ->whereIn('status', ['aprovado', 'pago'])
            ->whereNull('data_emissao')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->numero_processo,
                'motivo' => $solicitacao->motivo,
                'observacoes' => $solicitacao->observacoes,
                'status' => $solicitacao->status,
                'responsavel_instituicao_id' => $solicitacao->instituicaoResponsavelId(),
                'instituicao_tutora_id' => $solicitacao->instituicao_tutora_id,
                'estado_pagamento' => $solicitacao->estado_pagamento ?? 'pendente',
                'data_pagamento_confirmado' => $solicitacao->data_pagamento_confirmado?->format('d/m/Y H:i'),
                'data_emissao' => $solicitacao->data_emissao?->format('d/m/Y H:i'),
                'documento_gerado' => (bool) $solicitacao->data_emissao,
                'data_levantamento' => $solicitacao->data_levantamento?->format('d/m/Y H:i'),
                'aluno' => $solicitacao->aluno?->user?->nome,
                'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                'rupe_referencia' => $solicitacao->rupe_referencia,
                'rupe_entidade' => $solicitacao->rupe_entidade,
                'rupe_valor' => $solicitacao->rupe_valor,
                'can_decidir' => $this->decidir($solicitacao),
                'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                'can_delete' => $this->deletePermission($solicitacao),
            ]);

        return Inertia::render('dashboards/emissao/solicitacoes-documentos/index', [
            'solicitacoes' => $solicitacoes,
            'instituicao_id' => $user?->instituicao_id,
        ]);
    }
}