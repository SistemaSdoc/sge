<?php

namespace App\Notifications;

use App\Models\JustificativaNaoSubmissao;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JustificativaEnviadaNotificacao extends Notification
{
    use Queueable;

    public function __construct(
        public JustificativaNaoSubmissao $justificativa
    ) {}


    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $professor = $this->justificativa->professor?->user?->nome ?? 'N/A';
        $prazo = $this->justificativa->prazo;

        return (new MailMessage)
            ->subject(" Nova justificativa de {$professor}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line("O professor **{$professor}** enviou uma justificativa de não submissão.")
            ->line('**Detalhes:**')
            ->line("• Prazo: {$prazo?->titulo}")
            ->line("• Disciplina: {$prazo?->disciplina?->nome }")
            ->line("• Classe: {$prazo?->classe?->nome }")
            ->line("• Data: {$this->justificativa->data_justificativa->format('d/m/Y H:i')}")
            ->line('')
            ->line('**Motivo apresentado:**')
            ->line("_{$this->justificativa->motivo}_")
            ->action('Avaliar Justificativa', url("/dashboard/diretor/prazos/{$prazo?->id}/status"))
            ->line('Obrigado!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'              => 'justificativa_enviada',
            'justificativa_id'  => $this->justificativa->id,
            'prazo_id'          => $this->justificativa->prazo_prova_id,
            'professor_nome'    => $this->justificativa->professor?->user?->nome ?? 'N/A',
            'motivo'            => $this->justificativa->motivo,
            'data'              => $this->justificativa->data_justificativa->format('d/m/Y H:i'),
            'url'               => "/dashboard/diretor/prazos/{$this->justificativa->prazo_prova_id}/status",
        ];
    }
}