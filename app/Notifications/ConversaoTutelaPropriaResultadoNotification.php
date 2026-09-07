<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informa o colégio sobre a decisão da conversão para tutela própria.
 */
class ConversaoTutelaPropriaResultadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoDecisora,
        public string $cursoNome,
        public string $sharedId,
        public string $resultado,
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
        $aprovada = $this->resultado === 'aprovada';

        $mensagem = match ($this->resultado) {
            'pendente' => "O pedido de conversão do curso {$this->cursoNome} para tutela própria aguarda a aprovação da instituição {$this->instituicaoDecisora}.",
            'aprovada' => "A {$this->instituicaoDecisora} aprovou a conversão do curso {$this->cursoNome} para tutela própria.",
            default => "A {$this->instituicaoDecisora} rejeitou a conversão do curso {$this->cursoNome}. A tutela externa permanece activa.",
        };

        return [
            'tipo' => $this->resultado === 'pendente'
                ? 'conversao_tutela_propria_pendente'
                : 'conversao_tutela_propria_resultado',
            'titulo' => match ($this->resultado) {
                'pendente' => 'Conversão para tutela própria pendente',
                'aprovada' => 'Conversão para tutela própria aprovada',
                default => 'Conversão para tutela própria rejeitada',
            },
            'mensagem' => $mensagem,
            'curso_nome' => $this->cursoNome,
            'instituicao_decisora' => $this->instituicaoDecisora,
            'curso_tutelado_shared_id' => $this->sharedId,
            'resultado' => $this->resultado,
            'status' => $this->resultado,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(match ($this->resultado) {
                'pendente' => 'Conversão para tutela própria pendente',
                'aprovada' => 'Tutela própria aprovada',
                default => 'Conversão para tutela própria rejeitada',
            })
            ->markdown('mail.conversao-tutela-propria-resultado-notification', [
                'nomeInstituicaoDecisora' => $this->instituicaoDecisora,
                'nomeCurso' => $this->cursoNome,
                'resultado' => $this->resultado,
                'url' => $this->url ?: config('app.url').'/dashboard',
            ]);
    }
}
