<?php

namespace App\Services\Tenant\Pap\Trabalho;

use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\TrabalhoPapFeedback;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RegistarFeedbackTrabalhoPapService
{
    /**
     * Actualiza o estado do trabalho e regista o feedback do actor.
     */
    public function registar(
        TrabalhoPap $trabalho,
        User $actor,
        string $tipo,
        string $novoEstado,
        ?string $comentario,
        ?UploadedFile $ficheiroCorrecao = null,
        ?string $actorTenantId = null,
    ): TrabalhoPapFeedback {
        return DB::transaction(function () use (
            $trabalho,
            $actor,
            $tipo,
            $novoEstado,
            $comentario,
            $ficheiroCorrecao,
            $actorTenantId,
        ) {
            $estadoAnterior = $trabalho->status;
            $versaoAtual = $trabalho->versaoAtual;

            $trabalho->update(['status' => $novoEstado]);

            $caminhoCorrecao = null;
            $nomeOriginalCorrecao = null;

            if ($ficheiroCorrecao) {
                $prefixo = match ($tipo) {
                    TrabalhoPapFeedback::TIPO_CORRECAO_TUTOR => 'tutor',
                    TrabalhoPapFeedback::TIPO_CORRECAO_COORDENACAO => 'coord',
                    default => 'correcao',
                };

                $caminhoCorrecao = $ficheiroCorrecao->store(
                    "trabalhos_pap/{$trabalho->grupo_pap_id}/correcoes/{$prefixo}-v{$versaoAtual?->numero_versao}",
                    'private'
                );

                $nomeOriginalCorrecao = $ficheiroCorrecao->getClientOriginalName();
            }

            $isExternalActor = $actorTenantId !== null
                && $actorTenantId !== (string) tenancy()->tenant->getTenantKey();

            return TrabalhoPapFeedback::create([
                'trabalho_pap_id' => $trabalho->id,
                'versao_id' => $versaoAtual?->id,
                'utilizador_id' => $isExternalActor ? null : $actor->id,
                'utilizador_externo_id' => $isExternalActor ? $actor->id : null,
                'utilizador_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'utilizador_nome' => $isExternalActor ? $actor->nome : null,
                'tipo' => $tipo,
                'comentario' => $comentario,
                'caminho_ficheiro_correcao' => $caminhoCorrecao,
                'nome_original_correcao' => $nomeOriginalCorrecao,
                'estado_anterior' => $estadoAnterior,
                'estado_novo' => $novoEstado,
            ]);
        });
    }
}
