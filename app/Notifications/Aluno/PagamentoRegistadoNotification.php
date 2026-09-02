<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\Pagamento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PagamentoRegistadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pagamento $pagamento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Gera o recibo se ainda não existir
        $this->pagamento->gerarRecibo();

        $mail = (new MailMessage)
            ->subject('Pagamento registado com sucesso')
            ->view('mail.aluno.pagamento-registado', [
                'nome' => $notifiable->nome,
                'valorTotal' => number_format($this->pagamento->valor_total, 2, ',', '.'),
                'dataPagamento' => $this->pagamento->data_pagamento->format('d/m/Y'),
                'metodo' => $this->pagamento->metodo,
                'referencia' => $this->pagamento->referencia,
                'numeroRecibo' => $this->pagamento->numero_recibo,
            ]);

        // Anexa o recibo PDF se existir
        if ($this->pagamento->recibo_path && Storage::disk('local')->exists($this->pagamento->recibo_path)) {
            $mail->attach(
                Storage::disk('local')->path($this->pagamento->recibo_path),
                [
                    'as' => "recibo-{$this->pagamento->numero_recibo}.pdf",
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'pagamento_registado',
            'titulo' => 'Pagamento registado',
            'mensagem' => 'O seu pagamento de '.number_format($this->pagamento->valor_total, 2, ',', '.').' AOA foi registado com sucesso.',
            'pagamento_id' => $this->pagamento->id,
            'valor_total' => $this->pagamento->valor_total,
            'data_pagamento' => $this->pagamento->data_pagamento->format('d/m/Y'),
            'numero_recibo' => $this->pagamento->numero_recibo,
        ];
    }
}
