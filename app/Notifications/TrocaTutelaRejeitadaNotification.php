<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informa o colégio de que a instituição tutora rejeitou a troca.
 */
class TrocaTutelaRejeitadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoRejeitou,
        public string $instituicaoProposta,
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
            'tipo' => 'troca_tutela_rejeitada',
            'titulo' => 'Troca de tutela rejeitada',
            'mensagem' => "O {$this->instituicaoRejeitou} rejeitou a troca de tutela do curso {$this->cursoNome} para o {$this->instituicaoProposta}. A tutela actual permanece activa.",
            'curso_nome' => $this->cursoNome,
            'instituicao_rejeitou' => $this->instituicaoRejeitou,
            'instituicao_proposta' => $this->instituicaoProposta,
            'curso_tutelado_shared_id' => $this->sharedId,
            'status' => 'rejeitado',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaultUrl = config('app.url').'/dashboard';
        $finalUrl = $this->url ?: $defaultUrl;

        return (new MailMessage)
            ->subject('Troca de tutela rejeitada')
            ->markdown('mail.troca-tutela-rejeitada-notification', [
                'nomeInstituicaoRejeitou' => $this->instituicaoRejeitou,
                'nomeInstituicaoProposta' => $this->instituicaoProposta,
                'nomeCurso' => $this->cursoNome,
                'url' => $finalUrl,
            ]);
    }
}
