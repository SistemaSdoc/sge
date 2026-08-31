<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica o administrador tutor sobre uma nova solicitação.
 */
class SolicitacaoTutelaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoTutelada,
        public string $cursoNome,
        public string $sharedId,
        public string $url = '',
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filter_var($notifiable->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'solicitacao_tutela',
            'titulo' => 'Nova solicitação de tutela',
            'mensagem' => "A instituição {$this->instituicaoTutelada} solicita tutela para o curso {$this->cursoNome}.",
            'curso_nome' => $this->cursoNome,
            'instituicao_tutelada' => $this->instituicaoTutelada,
            'curso_tutelado_shared_id' => $this->sharedId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // 🔥 NÃO USAR url() helper aqui!
        // Se $url não foi passado, usa um fallback seguro
        $defaultUrl = config('app.url').'/dashboard';
        $finalUrl = $this->url ?: $defaultUrl;

        return (new MailMessage)
            ->subject('Nova solicitação de tutela')
            ->markdown('mail.solicitacao-tutela-notification', [
                'nomeInstituicao' => $this->instituicaoTutelada,
                'nomeCurso' => $this->cursoNome,
                'url' => $finalUrl,  // ← Já construída, sem chamar url()
            ]);
    }
}
