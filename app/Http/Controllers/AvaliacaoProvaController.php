<?php

namespace App\Http\Controllers;

use App\Models\SubmissaoProva;
use App\Notifications\SubmissaoAvaliadaNotificacao;  
use App\Services\ProvaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AvaliacaoProvaController extends Controller
{
    protected ProvaService $provaService;

    public function __construct(ProvaService $provaService)
    {
        $this->provaService = $provaService;
    }

    /**
     * Avalia uma submissão (aprova/rejeita).
     */
    public function update(Request $request, SubmissaoProva $submissao)
    {
        $this->authorize('avaliar', $submissao);

        // Verificação de instituição
        $instituicaoId = auth()->user()->instituicao_id;
        if ($submissao->prazo?->instituicao_id !== $instituicaoId) {
            abort(403, 'Esta submissão não pertence à sua instituição.');
        }

        $request->validate([
            'acao'    => 'required|in:aprovar,rejeitar',
            'parecer' => 'required_if:acao,rejeitar|nullable|string|max:500',
        ]);

        try {
            // Avaliar
            $submissao = $this->provaService->avaliarSubmissao(
                $submissao,
                $request->acao,
                $request->parecer
            );

            // 🔥 Notificar o professor
            $professor = $submissao->professor?->user;
            if ($professor) {
                // Carregar relações necessárias para o payload
                $submissao->load(['prazo.disciplina', 'prazo.classe', 'turma']);

                $professor->notify(new SubmissaoAvaliadaNotificacao($submissao));

                Log::info('📧 Notificação de avaliação enviada ao professor', [
                    'submissao_id'   => $submissao->id,
                    'professor_id'   => $submissao->professor_id,
                    'professor_user' => $professor->email,
                    'estado'         => $submissao->estado,
                ]);
            } else {
                Log::warning('⚠️ Submissão avaliada, mas professor/user não encontrado', [
                    'submissao_id' => $submissao->id,
                ]);
            }

            Log::info('Submissão avaliada', [
                'submissao_id'   => $submissao->id,
                'acao'           => $request->acao,
                'avaliado_por'   => auth()->id(),
                'instituicao_id' => $instituicaoId,
            ]);

            return back()->with('success', "Submissão {$request->acao}da com sucesso.");
        } catch (\Exception $e) {
            Log::error('Erro ao avaliar submissão', [
                'submissao_id' => $submissao->id,
                'error'        => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }
}