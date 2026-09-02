<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\PeriodoLancamentoNotas;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoLancamentoNotasDefinidoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PeriodoLancamentoNotas $periodo
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Prazo de lançamento de notas — {$this->periodo->periodo}º Trimestre")
            ->view('mail.professor.prazo-lancamento-notas', [
                'nome' => $notifiable->nome,
                'periodo' => $this->periodo->periodo,
                'dataInicio' => $this->periodo->data_inicio->format('d/m/Y H:i'),
                'dataLimite' => $this->periodo->data_limite->format('d/m/Y H:i'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'prazo_lancamento_notas',
            'titulo' => "Prazo de lançamento de notas — {$this->periodo->periodo}º Trimestre",
            'mensagem' => "O prazo de lançamento de notas do {$this->periodo->periodo}º trimestre foi definido. Início: {$this->periodo->data_inicio->format('d/m/Y H:i')} — Limite: {$this->periodo->data_limite->format('d/m/Y H:i')}.",
        ];
    }
}
