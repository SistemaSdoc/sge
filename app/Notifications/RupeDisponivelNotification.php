<?php

namespace App\Notifications;

use App\Models\Tenant\SolicitacaoDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RupeDisponivelNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SolicitacaoDocumento $solicitacao,
        public readonly ?string $categoria = 'aluno',
        public readonly ?string $url = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'rupe_disponivel',
            'categoria' => $this->categoria ?? 'aluno',
            'titulo' => 'Rupe disponível',
            'solicitacao_id' => $this->solicitacao->id,
            'tipo_documento' => $this->solicitacao->tipo_documento,
            'mensagem' => 'A tua solicitação foi aprovada, vai levantar o rupe para pagamento.',
            'rupe_referencia' => $this->solicitacao->rupe_referencia,
            'rupe_entidade' => $this->solicitacao->rupe_entidade,
            'rupe_valor' => $this->solicitacao->rupe_valor,
            'url' => $this->url ?? route('tenant.dashboard.solicitacoes-documentos.index'),
        ];
    }
}
