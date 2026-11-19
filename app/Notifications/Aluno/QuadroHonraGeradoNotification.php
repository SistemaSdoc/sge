<?php

namespace App\Notifications\Aluno;

use Illuminate\Notifications\Notification;

/** Informa os alunos da instituição quando um Quadro de Honra é gerado. */
class QuadroHonraGeradoNotification extends Notification
{
    public function __construct(
        public string $quadroHonraId,
        public string $anoLectivo,
        public int $periodo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $trimestre = "{$this->periodo}.º trimestre";

        return [
            'tipo' => 'quadro_honra_gerado',
            'titulo' => 'Novo Quadro de Honra disponível',
            'mensagem' => "O Quadro de Honra do {$trimestre} do ano lectivo de {$this->anoLectivo} foi gerado. Consulte a página do Quadro de Honra.",
            'quadro_honra_id' => $this->quadroHonraId,
            'ano_lectivo' => $this->anoLectivo,
            'periodo' => $this->periodo,
        ];
    }
}
