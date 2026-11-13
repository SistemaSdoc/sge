<?php

namespace App\Services\Tenant\Pap\Trabalho;

use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\TrabalhoPapFeedback;
use App\Models\Tenant\User;
use App\Traits\NotificaGrupoPap;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RevisaoTrabalhoPapCoordenacaoService
{
    use NotificaGrupoPap;

    /**
     * Define o serviço comum de registo de feedback.
     */
    public function __construct(
        private readonly RegistarFeedbackTrabalhoPapService $registarFeedbackService,
    ) {}

    /**
     * Aprova definitivamente o trabalho como coordenação.
     */
    public function aprovar(
        TrabalhoPap $trabalho,
        User $coordenador,
        ?string $comentario = null,
        ?string $actorTenantId = null,
    ): TrabalhoPapFeedback {
        if (! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            throw new \RuntimeException('O trabalho não está em análise da coordenação.');
        }

        return DB::transaction(function () use (
            $trabalho,
            $coordenador,
            $comentario,
            $actorTenantId,
        ) {
            $feedback = $this->registarFeedbackService->registar(
                $trabalho,
                $coordenador,
                TrabalhoPapFeedback::TIPO_APROVACAO_COORDENACAO,
                TrabalhoPap::STATUS_APROVADO,
                $comentario,
                null,
                $actorTenantId,
            );

            $isExternalActor = $actorTenantId !== null
                && $actorTenantId !== (string) tenancy()->tenant->getTenantKey();

            $trabalho->update([
                'aprovado_por_id' => $isExternalActor ? null : $coordenador->id,
                'aprovado_por_externo_id' => $isExternalActor ? $coordenador->id : null,
                'aprovado_por_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'aprovado_por_nome' => $isExternalActor ? $coordenador->nome : null,
                'data_aprovacao' => now(),
            ]);

            $this->notificarTrabalhoAprovado($trabalho->grupoPap);

            return $feedback;
        });
    }

    /**
     * Solicita ao aluno a correcção do trabalho como coordenação.
     *
     * O trabalho regressa ao estado de entrega pendente e volta a passar pelo tutor.
     */
    public function solicitarCorrecao(
        TrabalhoPap $trabalho,
        User $coordenador,
        string $comentario,
        ?UploadedFile $ficheiroCorrecao = null,
        ?string $actorTenantId = null,
    ): TrabalhoPapFeedback {
        if (! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            throw new \RuntimeException('O trabalho não está em análise da coordenação.');
        }

        $feedback = $this->registarFeedbackService->registar(
            $trabalho,
            $coordenador,
            TrabalhoPapFeedback::TIPO_CORRECAO_COORDENACAO,
            TrabalhoPap::STATUS_CORRECAO_COORDENACAO,
            $comentario,
            $ficheiroCorrecao,
            $actorTenantId,
        );

        $this->notificarCorrecaoSolicitada($trabalho->grupoPap, $comentario, 'coordenacao');

        return $feedback;
    }
}
