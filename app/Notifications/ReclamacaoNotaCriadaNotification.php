<?php

namespace App\Notifications;

use App\Models\Tenant\ReclamacaoNota;
use Illuminate\Notifications\Notification;

class ReclamacaoNotaCriadaNotification extends Notification
{
    public function __construct(public ReclamacaoNota $reclamacao) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'reclamacao_nota_criada',
            'titulo' => 'Nova reclamação de nota',
            'mensagem' => 'Recebeste uma reclamação de nota para análise.',
            'url' => route('tenant.dashboard.reclamacoes-notas.index'),
        ];
    }
}
