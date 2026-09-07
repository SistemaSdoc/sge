<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica o instituto actual para aprovar a troca de tutela.
 */
class TrocaTutelaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoNova,
        public string $instituicaoTutelada,
        public string $cursoNome,
        public string $sharedId,
        public string $tenantTutorAnteriorId,
        public ?string $cursoTuteladoSharedAnteriorId = null,
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
            'tipo' => 'troca_tutela',
            'titulo' => 'Solicitação para troca de tutela',
            'mensagem' => "O {$this->instituicaoTutelada} solicitou trocar a tutela do curso {$this->cursoNome} para o instituto {$this->instituicaoNova}. Aprove ou rejeite a troca.",
            'curso_nome' => $this->cursoNome,
            'instituicao_nova' => $this->instituicaoNova,
            'instituicao_tutelada' => $this->instituicaoTutelada,
            'curso_tutelado_shared_id' => $this->sharedId,
            'curso_tutelado_shared_anterior_id' => $this->cursoTuteladoSharedAnteriorId,
            'tenant_tutor_anterior_id' => $this->tenantTutorAnteriorId,
            'status' => 'pendente',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaultUrl = config('app.url').'/dashboard';
        $finalUrl = $this->url ?: $defaultUrl;

        return (new MailMessage)
            ->subject('Solicitação para troca de tutela')
            ->markdown('mail.solicitacao-troca-tutela-notification', [
                'nomeInstituicaoSolicitante' => $this->instituicaoTutelada,
                'nomeInstituicaoNova' => $this->instituicaoNova,
                'nomeCurso' => $this->cursoNome,
                'url' => $finalUrl,
            ]);
    }
}
