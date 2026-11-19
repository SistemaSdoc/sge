<?php

namespace App\Notifications\Aluno;

use Illuminate\Notifications\Notification;

/** Felicita o aluno através do canal interno de notificações da aplicação. */
class AlunoQuadroHonraNotification extends Notification
{
    public function __construct(
        public string $quadroHonraId,
        public string $anoLectivo,
        public int $posicao,
        public float $media,
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
            'tipo' => 'quadro_honra',
            'titulo' => 'Felicitações! Foi distinguido(a) com a inclusão no Quadro de Honra.',
            'mensagem' => "Informa-se que foi distinguido(a) com a inclusão no Quadro de Honra, do {$trimestre} do ano letivo de {$this->anoLectivo}, tendo alcançado a {$this->posicao}.ª classificação, com a média trimestral de ".number_format($this->media, 2, ',', '.').' valores.',
            'quadro_honra_id' => $this->quadroHonraId,
            'ano_lectivo' => $this->anoLectivo,
            'posicao' => $this->posicao,
            'media' => $this->media,
            'periodo' => $this->periodo,
        ];
    }
}
