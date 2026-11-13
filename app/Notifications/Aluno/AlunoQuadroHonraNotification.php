<?php

namespace App\Notifications\Aluno;

use Illuminate\Notifications\Notification;

/** Felicita o aluno através do canal interno de notificações da aplicação. */
class AlunoQuadroHonraNotification extends Notification
{
    public function __construct(
        public string $quadroHonraId,
        public string $anoLectivo,
        public string $tipo,
        public string $curso,
        public int $posicao,
        public float $media,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $tipoQuadro = $this->tipo === 'geral'
            ? 'geral'
            : "do curso de {$this->curso}";

        return [
            'tipo' => 'quadro_honra',
            'titulo' => 'Felicitações! Foi distinguido(a) com a inclusão no Quadro de Honra.',
            'mensagem' => "Informa-se que foi distinguido(a) com a inclusão no Quadro de Honra {$tipoQuadro}, do ano letivo de {$this->anoLectivo}, tendo alcançado a {$this->posicao}.ª classificação, com a média final de ".number_format($this->media, 1, ',', '.').' valores.',
            'quadro_honra_id' => $this->quadroHonraId,
            'ano_lectivo' => $this->anoLectivo,
            'tipo_quadro' => $this->tipo,
            'curso' => $this->curso,
            'posicao' => $this->posicao,
            'media' => $this->media,
        ];
    }
}
