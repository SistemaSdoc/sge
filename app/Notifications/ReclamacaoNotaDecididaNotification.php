<?php

namespace App\Notifications;

use App\Models\Tenant\ReclamacaoNota;
use Illuminate\Notifications\Notification;

class ReclamacaoNotaDecididaNotification extends Notification
{
    public function __construct(public ReclamacaoNota $reclamacao) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titulo = match ($this->reclamacao->status) {
            'em_analise' => 'Reclamação em análise',
            'aguarda_informacao' => 'Informação solicitada para a reclamação',
            'aprovada' => 'Reclamação aprovada',
            'reprovada' => 'Reclamação reprovada',
            default => 'Reclamação atualizada',
        };

        $mensagem = match ($this->reclamacao->status) {
            'em_analise' => 'O professor ou membro do grupo começou a analisar a sua reclamação.',
            'aguarda_informacao' => 'É necessária informação adicional para continuar a análise da sua reclamação.',
            'aprovada' => 'A sua reclamação foi aprovada e a nota foi corrigida.',
            'reprovada' => 'A sua reclamação foi reprovada e a nota original foi mantida.',
            default => 'O estado da sua reclamação foi atualizado.',
        };

        return [
            'tipo' => 'reclamacao_nota_decidida',
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => route('tenant.dashboard.reclamacoes-notas.index'),
        ];
    }
}
