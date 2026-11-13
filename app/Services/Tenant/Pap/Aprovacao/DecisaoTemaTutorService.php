<?php

namespace App\Services\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\User;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\DB;

class DecisaoTemaTutorService
{
    use NotificaGrupoPap;

    /**
     * Encaminha a proposta aprovada pelo tutor para a coordenação.
     */
    public function enviarParaCoordenacao(
        GrupoPap $grupoPap,
        User $tutor,
        ?string $comentario = null,
    ): bool {
        if (! $grupoPap->podeSerAprovadoPeloTutor()) {
            return false;
        }

        DB::transaction(function () use ($grupoPap, $tutor, $comentario): void {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_PENDENTE,
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $tutor->id,
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_PENDENTE,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'comentario' => $comentario ?? 'Aprovado pelo professor tutor.',
            ]);
        });

        $this->notificarTemaValidadoPeloTutor($grupoPap);

        return true;
    }

    /**
     * Solicita melhorias à proposta antes do envio para a coordenação.
     */
    public function solicitarMelhoria(
        GrupoPap $grupoPap,
        User $tutor,
        string $recomendacao,
    ): bool {
        if (! $grupoPap->podeSerAprovadoPeloTutor()) {
            return false;
        }

        DB::transaction(function () use ($grupoPap, $tutor, $recomendacao): void {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'comentario_aprovacao' => $recomendacao,
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $tutor->id,
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'comentario' => $recomendacao,
            ]);
        });

        $grupoPap->loadMissing('alunos.user');
        $this->notificarMelhoriasSolicitadas($grupoPap, 'tutor');

        return true;
    }
}
