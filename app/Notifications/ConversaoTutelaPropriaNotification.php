<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica a instituição tutora sobre o pedido de conversão para tutela própria.
 */
class ConversaoTutelaPropriaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoSolicitante,
        public string $instituicaoActual,
        public string $cursoNome,
        public string $sharedId,
        public string $tenantTutorAnteriorId,
        public string $url = '',
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filter_var($notifiable->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'conversao_tutela_propria',
            'titulo' => 'Pedido de conversão para tutela própria',
            'mensagem' => "A {$this->instituicaoSolicitante} solicitou deixar de ter tutela externa no curso {$this->cursoNome}. Aprove ou rejeite a conversão.",
            'curso_nome' => $this->cursoNome,
            'instituicao_solicitante' => $this->instituicaoSolicitante,
            'instituicao_actual' => $this->instituicaoActual,
            'curso_tutelado_shared_id' => $this->sharedId,
            'tenant_tutor_anterior_id' => $this->tenantTutorAnteriorId,
            'status' => 'pendente',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pedido de conversão para tutela própria')
            ->markdown('mail.conversao-tutela-propria-notification', [
                'nomeInstituicaoSolicitante' => $this->instituicaoSolicitante,
                'nomeCurso' => $this->cursoNome,
                'url' => $this->url ?: config('app.url').'/dashboard',
            ]);
    }
}
