<?php

namespace App\Notifications\Aluno;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropinaEmAtrasoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $totalMeses,
        public float $valorTotal,
        public array $meses,
        public string $assinatura,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Propina em atraso')
            ->view('mail.aluno.propina-em-atraso', [
                'nome'        => $notifiable->nome,
                'totalMeses'  => $this->totalMeses,
                'valorTotal'  => number_format($this->valorTotal, 2, ',', '.'),
                'meses'       => $this->meses,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'       => 'propina_atraso',
            'titulo'     => "Propina em atraso ({$this->totalMeses} mês(es))",
            'mensagem'   => "Tens {$this->totalMeses} mês(es) de propina em atraso, no total de ".number_format($this->valorTotal, 2, ',', '.').' AOA.',
            'meses'      => $this->meses,
            'valor_total' => $this->valorTotal,
            'assinatura' => $this->assinatura,
        ];
    }
}