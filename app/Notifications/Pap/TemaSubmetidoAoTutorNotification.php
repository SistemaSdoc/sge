<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TemaSubmetidoAoTutorNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tema_submetido_tutor',
            'titulo' => 'Novo tema aguarda validação',
            'mensagem' => "O grupo \"{$this->grupoPap->nome_grupo}\" submeteu o tema \"{$this->grupoPap->tema_grupo}\" para a sua validação.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => "/grupos-pap/{$this->grupoPap->id}",
        ];
    }
}
