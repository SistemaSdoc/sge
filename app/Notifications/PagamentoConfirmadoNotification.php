<?php

namespace App\Notifications;

use App\Models\Tenant\SolicitacaoDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PagamentoConfirmadoNotification extends Notification
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
            'tipo' => 'pagamento_confirmado',
            'categoria' => $this->categoria ?? 'aluno',
            'titulo' => 'Pagamento confirmado',
            'solicitacao_id' => $this->solicitacao->id,
            'mensagem' => 'Confirmámos o teu pagamento, o teu documento está a ser preparado.',
            'url' => $this->url ?? route('tenant.dashboard.solicitacoes-documentos.index'),
        ];
    }
}
