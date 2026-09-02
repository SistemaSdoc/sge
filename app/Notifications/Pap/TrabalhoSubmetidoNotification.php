<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TrabalhoSubmetidoNotification extends Notification
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
            'tipo' => 'trabalho_submetido',
            'titulo' => 'Novo trabalho submetido',
            'mensagem' => "O grupo \"{$this->grupoPap->nome_grupo}\" submeteu uma nova versão do trabalho PAP para revisão.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => "/grupos-pap/{$this->grupoPap->id}",
        ];
    }
}
