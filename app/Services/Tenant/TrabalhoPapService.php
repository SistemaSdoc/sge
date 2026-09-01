<?php

namespace App\Services\Tenant;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\TrabalhoPapFeedback;
use App\Models\Tenant\TrabalhoPapVersao;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapService
{
    use NotificaGrupoPap;

    /**
     * Cria o registo do trabalho quando o tema é aprovado.
     * Chamado pelo AprovacaoTemaService.
     */
    public function inicializar(GrupoPap $grupoPap): TrabalhoPap
    {
        return TrabalhoPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'status' => TrabalhoPap::STATUS_PENDENTE_ENTREGA,
        ]);
    }

    /**
     * Aluno submete um novo PDF.
     * Cria uma nova versão e avança o status para em_analise_tutor.
     */
    public function submeter(TrabalhoPap $trabalho, User $user, UploadedFile $ficheiro): TrabalhoPapVersao
    {
        if (!$trabalho->podeSerSubmetido()) {
            throw new \RuntimeException('O trabalho não pode ser submetido neste momento.');
        }

        return DB::transaction(function () use ($trabalho, $user, $ficheiro) {

            $numeroVersao = $trabalho->versoes()->max('numero_versao') + 1;

            $caminho = $ficheiro->storeAs(
                "trabalhos_pap/{$trabalho->grupo_pap_id}",
                "v{$numeroVersao}_{$ficheiro->getClientOriginalName()}",
                'private'
            );

            $versao = TrabalhoPapVersao::create([
                'trabalho_pap_id' => $trabalho->id,
                'submetido_por_id' => $user->id,
                'numero_versao' => $numeroVersao,
                'caminho_ficheiro' => $caminho,
                'nome_original' => $ficheiro->getClientOriginalName(),
                'status_quando_submetido' => $trabalho->status,
            ]);

            $trabalho->update(['status' => TrabalhoPap::STATUS_EM_ANALISE_TUTOR]);

            // ── Notificações ──────────────────────────────────────
            // ── Notificações ──────────────────────────────────────
            $grupoPap = $trabalho->grupoPap->load('professor.user');
            $tutor = $grupoPap->professor?->user;
            $revisores = collect($tutor ? [$tutor] : []);
            $this->notificarTrabalhoSubmetido($grupoPap, $revisores);
            // ──────────────────────────────────────────────────────
            // ──────────────────────────────────────────────────────

            return $versao;
        });
    }
    /**
     * Tutor aprova o trabalho e envia para a coordenação.
     */
    public function aprovarComoTutor(TrabalhoPap $trabalho, User $user, ?string $comentario = null): TrabalhoPapFeedback
    {
        if (!$trabalho->podeSerAnalisadoPeloTutor()) {
            throw new \RuntimeException('O trabalho não está em análise do tutor.');
        }

        $feedback = $this->registarFeedback(
            $trabalho,
            $user,
            TrabalhoPapFeedback::TIPO_APROVACAO_TUTOR,
            TrabalhoPap::STATUS_EM_ANALISE_COORDENACAO,
            $comentario
        );

        // ── Notificações ─────────────────────────────────────────
        // Avisa a coordenação que o trabalho chegou para análise
        $grupoPap = $trabalho->grupoPap;
        $coordenadores = $this->resolverCoordenadores($grupoPap);
        $this->notificarTrabalhoSubmetido($grupoPap, $coordenadores);
        // ──────────────────────────────────────────────────────────

        return $feedback;
    }

    /**
     * Tutor solicita correção ao aluno.
     */
    public function solicitarCorrecaoComoTutor(TrabalhoPap $trabalho, User $user, string $comentario, ?UploadedFile $ficheiroCorrecao = null): TrabalhoPapFeedback
    {
        if (!$trabalho->podeSerAnalisadoPeloTutor()) {
            throw new \RuntimeException('O trabalho não está em análise do tutor.');
        }

        $feedback = $this->registarFeedback(
            $trabalho,
            $user,
            TrabalhoPapFeedback::TIPO_CORRECAO_TUTOR,
            TrabalhoPap::STATUS_CORRECAO_TUTOR,
            $comentario,
            $ficheiroCorrecao
        );

        // ── Notificações ──────────────────────────────────────────
        $this->notificarCorrecaoSolicitada($trabalho->grupoPap, $comentario, 'tutor');
        // ──────────────────────────────────────────────────────────

        return $feedback;
    }

    /**
     * Coordenação aprova o trabalho definitivamente.
     */
    public function aprovarComoCoordenacao(TrabalhoPap $trabalho, User $user, ?string $comentario = null): TrabalhoPapFeedback
    {
        if (!$trabalho->podeSerAnalisadoPelaCoordenacao()) {
            throw new \RuntimeException('O trabalho não está em análise da coordenação.');
        }

        return DB::transaction(function () use ($trabalho, $user, $comentario) {

            $feedback = $this->registarFeedback(
                $trabalho,
                $user,
                TrabalhoPapFeedback::TIPO_APROVACAO_COORDENACAO,
                TrabalhoPap::STATUS_APROVADO,
                $comentario
            );

            $trabalho->update([
                'aprovado_por_id' => $user->id,
                'data_aprovacao' => now(),
            ]);

            // ── Notificações ──────────────────────────────────────
            $this->notificarTrabalhoAprovado($trabalho->grupoPap);
            // ──────────────────────────────────────────────────────

            return $feedback;
        });
    }

    /**
     * Coordenação solicita correção ao aluno.
     * O trabalho volta para pendente_entrega — o aluno
     * submete novamente e passa obrigatoriamente pelo tutor.
     */
    public function solicitarCorrecaoComoCoordenacao(TrabalhoPap $trabalho, User $user, string $comentario, ?UploadedFile $ficheiroCorrecao = null): TrabalhoPapFeedback
    {
        if (!$trabalho->podeSerAnalisadoPelaCoordenacao()) {
            throw new \RuntimeException('O trabalho não está em análise da coordenação.');
        }

        $feedback = $this->registarFeedback(
            $trabalho,
            $user,
            TrabalhoPapFeedback::TIPO_CORRECAO_COORDENACAO,
            TrabalhoPap::STATUS_CORRECAO_COORDENACAO,
            $comentario,
            $ficheiroCorrecao
        );

        // ── Notificações ──────────────────────────────────────────
        $this->notificarCorrecaoSolicitada($trabalho->grupoPap, $comentario, 'coordenacao');
        // ──────────────────────────────────────────────────────────

        return $feedback;
    }

    /**
     * Regista o feedback e atualiza o status do trabalho.
     * Método interno partilhado por todas as ações.
     */
    private function registarFeedback(
        TrabalhoPap $trabalho,
        User $user,
        string $tipo,
        string $novoStatus,
        ?string $comentario,
        ?UploadedFile $ficheiroCorrecao = null   // <-- novo
    ): TrabalhoPapFeedback {

        return DB::transaction(function () use ($trabalho, $user, $tipo, $novoStatus, $comentario, $ficheiroCorrecao) {

            $estadoAnterior = $trabalho->status;
            $versaoAtual = $trabalho->versaoAtual;

            $trabalho->update(['status' => $novoStatus]);

            // Guardar PDF corrigido se vier
            $caminhoCorrecao = null;
            $nomeOriginalCorrecao = null;

            if ($ficheiroCorrecao) {
                $prefixo = match ($tipo) {
                    TrabalhoPapFeedback::TIPO_CORRECAO_TUTOR => 'tutor',
                    TrabalhoPapFeedback::TIPO_CORRECAO_COORDENACAO => 'coord',
                    default => 'correcao',
                };

                $caminhoCorrecao = $ficheiroCorrecao->storeAs(
                    "trabalhos_pap/{$trabalho->grupo_pap_id}/correcoes",
                    "{$prefixo}_v{$versaoAtual?->numero_versao}_{$ficheiroCorrecao->getClientOriginalName()}",
                    'private'
                );
                $nomeOriginalCorrecao = $ficheiroCorrecao->getClientOriginalName();
            }

            return TrabalhoPapFeedback::create([
                'trabalho_pap_id' => $trabalho->id,
                'versao_id' => $versaoAtual?->id,
                'utilizador_id' => $user->id,
                'tipo' => $tipo,
                'comentario' => $comentario,
                'caminho_ficheiro_correcao' => $caminhoCorrecao,
                'nome_original_correcao' => $nomeOriginalCorrecao,
                'estado_anterior' => $estadoAnterior,
                'estado_novo' => $novoStatus,
            ]);
        });
    }

    /**
     * Resolve os utilizadores coordenadores do curso tutelado do grupo.
     */
    private function resolverCoordenadores(GrupoPap $grupoPap): \Illuminate\Support\Collection
    {
        $cursoTutelado = $grupoPap->turma
            ?->cursoClasseTurno
            ?->cursoClasse
                ?->cursoTutelado;

        if (!$cursoTutelado) {
            return collect();
        }

        return $cursoTutelado->professores()
            ->where('coordenador', 1)
            ->with('user')
            ->get()
            ->map->user
            ->filter();
    }
}