<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PropinaEmAtrasoNotification extends Notification
{
    public function __construct(
        public readonly int $totalPendencias,
        public readonly float $valorTotal,
        public readonly float $multaTotal,
        public readonly array $meses,
        public readonly string $assinatura,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $mesesTexto = implode(', ', $this->meses);

        $mensagem = $this->totalPendencias === 1
            ? "Tens 1 mês de propina em atraso: {$mesesTexto}."
            : "Tens {$this->totalPendencias} meses de propina em atraso: {$mesesTexto}.";

        if ($this->multaTotal > 0) {
            $mensagem .= ' Este valor já inclui multa por atraso.';
        }

        return [
            'tipo' => 'propina_atraso',
            'titulo' => 'Propinas em atraso',
            'mensagem' => $mensagem,
            'total_pendencias' => $this->totalPendencias,
            'valor_total' => $this->valorTotal,
            'multa_total' => $this->multaTotal,
            'meses' => $this->meses,
            'assinatura' => $this->assinatura,
        ];
    }
}