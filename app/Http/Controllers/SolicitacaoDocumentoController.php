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
     *
     * Modo "Estado dos pedidos" (predefinido): mostra apenas pedidos em curso
     * (exclui entregues e rejeitados).
     *
     * Modo "Histórico" (?ver=historico): mostra apenas pedidos com estado
     * final (rejeitado / entregue).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $aluno = optional($user)->aluno;
        $turmaAtual = $aluno?->turmaActual()->first() ?? $aluno?->turmas()->orderByDesc('created_at')->first();
        $cursoTuteladoAtual = $aluno?->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        $cursoAtual = $cursoTuteladoAtual?->instituicaoCurso?->curso;
        $classeAtual = $turmaAtual?->cursoClasseTurno?->cursoClasse?->classe;
        $anoLectivoAtual = $turmaAtual?->anoLectivo ?? $aluno?->inscricao?->anoLectivo;

        $classeAtual = $classeAtual ?? ($aluno?->turmaActual()->first()?->classe ?? null);

        $temTutelaInstituto = $cursoTuteladoAtual?->instituicaoTutora?->tipo === 'instituto';
        $podeCertificado = ($classeAtual?->emite_certificado ?? false) && $temTutelaInstituto;

        $tipos = config('documentos.types_base', []);

        if ($podeCertificado) {
            $tipos[] = config('documentos.certificado');
        }

        $modoHistorico = $request->query('ver') === 'historico';

        $solicitacoesQuery = $aluno
            ? $aluno->solicitacoesDocumentos()
            : null;

        if ($solicitacoesQuery) {
            if ($modoHistorico) {
                $solicitacoesQuery->whereIn('status', ['rejeitado', 'entregue']);
            } else {
                $solicitacoesQuery->whereNotIn('status', ['entregue', 'rejeitado']);
            }
        }

        $solicitacoes = $solicitacoesQuery
            ? $solicitacoesQuery
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
                ])
            : [];

        $elegibilidade = new ElegibilidadeDocumentoService;
        $classesDisponiveis = $aluno ? $elegibilidade->classesDisponiveisParaDeclaracao($aluno) : [];

        $bloqueiosTipo = [];
        if ($aluno) {
            foreach ($tipos as $tipoConfig) {
                $tipoValor = $tipoConfig['value'] ?? $tipoConfig;
                if (! is_string($tipoValor)) {
                    continue;
                }

                $bloqueio = $this->verificarBloqueioSolicitacao($aluno, $tipoValor);
                if ($bloqueio) {
                    $bloqueiosTipo[$tipoValor] = $bloqueio;
                }
            }
        }

        return Inertia::render('dashboards/aluno/solicitacoes-documentos/index', [
            'solicitacoes' => $solicitacoes,
            'tipos' => $tipos,
            'classes_disponiveis' => $classesDisponiveis,
            'pode_certificado' => $podeCertificado,
            'bloqueios_tipo' => $bloqueiosTipo,
            'ver' => $request->query('ver'),          // ← NOVO: 'historico' ou null
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

        $bloqueio = $this->verificarBloqueioSolicitacao($aluno, $validated['tipo_documento']);
        if ($bloqueio) {
            return back()->withErrors(['tipo_documento' => $bloqueio['mensagem']]);
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
            'instituicao_aprovadora_id' => null,
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
     * Página do colégio.
     * - Predefinido: Estado dos pedidos (em curso)
     * - ?ver=historico: Histórico (rejeitado + entregue)
     */
    public function colegioIndex(Request $request): Response
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
        $modoHistorico = $request->query('ver') === 'historico';

        $query = SolicitacaoDocumento::query()->where('instituicao_origem_id', $instituicaoId);

        if ($modoHistorico) {
            $query->whereIn('status', ['rejeitado', 'entregue']);
        } else {
            $query->whereNotIn('status', ['entregue', 'rejeitado']);
        }

        $solicitacoes = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
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
            'ver' => $request->query('ver'),          // ← NOVO
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
     * Página da tutela.
     * - Predefinido: Estado dos pedidos (em curso)
     * - ?ver=historico: Histórico (rejeitado + entregue)
     */
    public function tutelaIndex(Request $request): Response
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
        $modoHistorico = $request->query('ver') === 'historico';

        $queryLocais = SolicitacaoDocumento::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->where('instituicao_origem_id', $instituicaoId);

        $queryTuteladas = SolicitacaoDocumento::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->where('instituicao_origem_id', '!=', $instituicaoId);

        if ($modoHistorico) {
            $queryLocais->whereIn('status', ['rejeitado', 'entregue']);
            $queryTuteladas->whereIn('status', ['rejeitado', 'entregue']);
        } else {
            $queryLocais->whereNotIn('status', ['entregue', 'rejeitado']);
            $queryTuteladas->whereNotIn('status', ['entregue', 'rejeitado']);
        }

        $solicitacoesLocais = $queryLocais
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
                'origem' => $solicitacao->instituicaoOrigem?->nome ?? 'Instituição',
                'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                'can_decidir' => $this->decidir($solicitacao),
                'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                'can_delete' => $this->deletePermission($solicitacao),
            ]);

        $solicitacoesTuteladas = $queryTuteladas
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SolicitacaoDocumento $solicitacao) => [
                'id' => $solicitacao->id,
                'tipo_documento' => $solicitacao->tipo_documento,
                'tipo_label' => $solicitacao->tipoLabel,
                'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
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
            'ver' => $request->query('ver'),          // ← NOVO
            'instituicao_id' => $instituicaoId,
        ]);
    }

    /**
     * Retorna o HISTÓRICO de solicitações para uso dinâmico via AJAX.
     * (Mantém-se igual — devolve só rejeitado/entregue.)
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $estadosFinais = ['rejeitado', 'entregue'];

        if ($user->hasRole('Aluno') || $user->hasRole('Candidato')) {
            $aluno = $user->aluno;
            if (! $aluno) {
                return response()->json(['data' => []]);
            }

            $solicitacoes = $aluno->solicitacoesDocumentos()
                ->whereIn('status', $estadosFinais)
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
                ]);

            return response()->json(['data' => $solicitacoes]);
        }

        $instituicao = $user?->instituicao;
        if (! $instituicao) {
            return response()->json(['data' => []]);
        }

        $instituicaoId = $instituicao->id;

        if ($instituicao->tipo === 'colegio') {
            $solicitacoes = SolicitacaoDocumento::query()
                ->where('instituicao_origem_id', $instituicaoId)
                ->whereIn('status', $estadosFinais)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn(SolicitacaoDocumento $solicitacao) => [
                    'id' => $solicitacao->id,
                    'tipo_documento' => $solicitacao->tipo_documento,
                    'tipo_label' => $solicitacao->tipoLabel,
                    'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                    'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
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

            return response()->json(['data' => $solicitacoes]);
        }

        if ($instituicao->tipo === 'instituto') {
            $solicitacoes = SolicitacaoDocumento::query()
                ->where('instituicao_tutora_id', $instituicaoId)
                ->whereIn('status', $estadosFinais)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn(SolicitacaoDocumento $solicitacao) => [
                    'id' => $solicitacao->id,
                    'tipo_documento' => $solicitacao->tipo_documento,
                    'tipo_label' => $solicitacao->tipoLabel,
                    'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                    'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
                    'origem' => $solicitacao->instituicaoOrigem?->nome ?? 'Instituição',
                    'created_at' => $solicitacao->created_at?->format('d/m/Y H:i'),
                    'can_decidir' => $this->decidir($solicitacao),
                    'can_marcar_pago' => $this->marcarComoPago($solicitacao),
                    'can_marcar_pronto' => $this->marcarComoPronto($solicitacao),
                    'can_marcar_levantado' => $this->marcarComoLevantadoPermission($solicitacao),
                    'can_delete' => $this->deletePermission($solicitacao),
                ]);

            return response()->json(['data' => $solicitacoes]);
        }

        return response()->json(['data' => []]);
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

        $solicitacao->status = 'pendente';
        $solicitacao->instituicao_aprovadora_id = $user->instituicao_id;
        $solicitacao->data_aprovacao = now();
        $solicitacao->save();

        return back()->with('success', 'Pedido de ' . $solicitacao->tipoLabel . ' encaminhado para a instituição tutora para análise.');
    }

    // -----------------------------------------------------------------------
    // REGRAS DE BLOQUEIO
    // -----------------------------------------------------------------------

    private function verificarBloqueioSolicitacao($aluno, string $tipoDocumento): ?array
    {
        $emCurso = $aluno->solicitacoesDocumentos()
            ->where('tipo_documento', $tipoDocumento)
            ->whereNotIn('status', ['entregue', 'rejeitado'])
            ->exists();

        if ($emCurso) {
            return [
                'motivo' => 'em_curso',
                'mensagem' => 'Já tem uma solicitação deste tipo de documento em curso. Aguarde a conclusão do processo (levantamento ou rejeição) antes de solicitar novamente.',
                'disponivel_em' => null,
            ];
        }

        // Removido: regra de prazo/cooldown configurável. Mantém-se apenas o bloqueio
        // que impede nova solicitação do mesmo tipo enquanto existir uma em curso.
        return null;
    }

    // -----------------------------------------------------------------------
    // AUTORIZAÇÃO
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

        if (! $user->instituicao_id || ! $user->hasAnyRole(['Director', 'Secretaria'])) {
            return false;
        }

        return $entregue && $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    // -----------------------------------------------------------------------
    // AÇÕES
    // -----------------------------------------------------------------------

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

    public function emitir(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        $validated = $request->validate([
            'numero_registro_tutora' => 'required|string|max:255',
        ]);

        if (! $this->marcarComoPronto($solicitacao)) {
            abort(403, 'Sem permissão para marcar como pronto.');
        }

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

        return back()->with(
            'success',
            'A emissão do(a) ' . $solicitacao->tipoLabel . ' foi concluída com sucesso. O documento encontra-se disponível para levantamento na secretaria, pelo que se solicita ao requerente que se dirija às instalações para o efeito.'
        );
    }

    public function marcarComoLevantado(Request $request, SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->marcarComoLevantadoPermission($solicitacao)) {
            abort(403, 'Sem permissão para marcar como levantado.');
        }

        if (! $solicitacao->data_emissao && $solicitacao->status !== 'pronto') {
            abort(422, 'Só é possível registar o levantamento depois de o documento estar pronto.');
        }

        if ($solicitacao->data_levantamento || $solicitacao->status === 'entregue') {
            return back()->with('info', 'O(A) ' . $solicitacao->tipoLabel . ' já se encontra registado(a) como levantado(a).');
        }

        $solicitacao->marcarComoLevantado(Auth::user());

        return back()->with(
            'success',
            'O levantamento do(a) ' . $solicitacao->tipoLabel . ' foi registado com sucesso. O processo considera-se, assim, concluído.'
        );
    }

    public function destroy(SolicitacaoDocumento $solicitacao): RedirectResponse
    {
        if (! $this->deletePermission($solicitacao)) {
            abort(403, 'Sem permissão para eliminar.');
        }

        $solicitacao->delete();

        return back()->with('success', 'Pedido apagado com sucesso.');
    }

    // -----------------------------------------------------------------------
    // PÁGINAS DE EMISSÃO
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
            'numero_processo' => $solicitacao->aluno?->numero_processo ?? $solicitacao->numero_processo,
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
                'numero_estudante' => $solicitacao->aluno?->user?->numero_estudante,
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
