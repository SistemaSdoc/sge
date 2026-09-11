<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SolicitacaoDocumentoStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $titulo,
        public readonly string $mensagem,
        public readonly string $solicitacaoId,
        public readonly ?string $tipoDocumento = null,
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
            'tipo' => 'solicitacao_documento_status',
            'categoria' => $this->categoria ?? 'aluno',
            'solicitacao_id' => $this->solicitacaoId,
            'tipo_documento' => $this->tipoDocumento,
            'titulo' => $this->titulo,
            'mensagem' => $this->mensagem,
            'url' => $this->url ?? route('tenant.dashboard.solicitacoes-documentos.index'),
        ];
    }
}
