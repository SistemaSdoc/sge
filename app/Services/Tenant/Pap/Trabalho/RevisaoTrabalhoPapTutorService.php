<?php

namespace App\Services\Tenant\Pap\Trabalho;

use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\TrabalhoPapFeedback;
use App\Models\Tenant\User;
use App\Traits\NotificaGrupoPap;
use Illuminate\Http\UploadedFile;

class RevisaoTrabalhoPapTutorService
{
    use NotificaGrupoPap;

    /**
     * Define o serviço comum de registo de feedback.
     */
    public function __construct(
        private readonly RegistarFeedbackTrabalhoPapService $registarFeedbackService,
    ) {}

    /**
     * Aprova o trabalho como tutor e envia-o para análise da coordenação.
     */
    public function aprovar(
        TrabalhoPap $trabalho,
        User $tutor,
        ?string $comentario = null,
    ): TrabalhoPapFeedback {
        if (! $trabalho->podeSerAnalisadoPeloTutor()) {
            throw new \RuntimeException('O trabalho não está em análise do tutor.');
        }

        $feedback = $this->registarFeedbackService->registar(
            $trabalho,
            $tutor,
            TrabalhoPapFeedback::TIPO_APROVACAO_TUTOR,
            TrabalhoPap::STATUS_EM_ANALISE_COORDENACAO,
            $comentario,
        );

        $this->notificarTrabalhoAosCoordenadores($trabalho->grupoPap);

        return $feedback;
    }

    /**
     * Solicita ao aluno a correcção do trabalho como tutor.
     */
    public function solicitarCorrecao(
        TrabalhoPap $trabalho,
        User $tutor,
        string $comentario,
        ?UploadedFile $ficheiroCorrecao = null,
    ): TrabalhoPapFeedback {
        if (! $trabalho->podeSerAnalisadoPeloTutor()) {
            throw new \RuntimeException('O trabalho não está em análise do tutor.');
        }

        $feedback = $this->registarFeedbackService->registar(
            $trabalho,
            $tutor,
            TrabalhoPapFeedback::TIPO_CORRECAO_TUTOR,
            TrabalhoPap::STATUS_CORRECAO_TUTOR,
            $comentario,
            $ficheiroCorrecao,
        );

        $this->notificarCorrecaoSolicitada($trabalho->grupoPap, $comentario, 'tutor');

        return $feedback;
    }
}
