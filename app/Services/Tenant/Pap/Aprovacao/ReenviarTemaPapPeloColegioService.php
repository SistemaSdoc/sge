<?php

namespace App\Services\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\User;
use App\Notifications\Pap\TemaSubmetidoAoTutorNotification;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\DB;

class ReenviarTemaPapPeloColegioService
{
    use NotificaGrupoPap;

    /**
     * Reenvia a proposta corrigida para nova análise do tutor.
     *
     * @param  array{
     *     nome_grupo: string,
     *     tema_grupo: string,
     *     problema?: string|null,
     *     objectivo_geral?: string|null,
     *     objectivos_especificos?: string|null
     * }  $dados
     */
    public function reenviar(
        GrupoPap $grupoPap,
        User $utilizador,
        array $dados,
    ): bool {
        if (! $grupoPap->podeSerReenviado()) {
            return false;
        }

        return DB::transaction(function () use ($grupoPap, $utilizador, $dados): bool {
            $estadoAnterior = $grupoPap->status_aprovacao;

            $grupoPap->update([
                'nome_grupo' => $dados['nome_grupo'],
                'tema_grupo' => $dados['tema_grupo'],
                'problema' => $dados['problema'] ?? null,
                'objectivo_geral' => $dados['objectivo_geral'] ?? null,
                'objectivos_especificos' => $dados['objectivos_especificos'] ?? null,
                'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
                'aprovado_por_id' => null,
                'data_aprovacao' => null,
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $utilizador->id,
                'estado_anterior' => $estadoAnterior,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'estado_novo' => GrupoPap::APROVACAO_SUBMETIDO,
                'comentario' => 'Tema corrigido e reenviado para revisão do professor tutor.',
            ]);

            $tutor = $grupoPap->professor?->user;

            if ($tutor) {
                $tutor->notify(new TemaSubmetidoAoTutorNotification($grupoPap));
            }

            $this->notificarTemaSubmetidoConfirmacaoAlunos($grupoPap);

            return true;
        });
    }
}
