<?php

namespace App\Notifications\Aluno;

use App\Notifications\Concerns\ReliableNotification;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerfilIncompletoNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;
    use TenantNotificationUrl;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ação necessária: complete o seu perfil')
            ->view('mail.aluno.perfil-incompleto', [
                'nome' => $notifiable->nome,
                'url' => $this->tenantNotificationUrl('tenant.dashboard.profile.edit'),
                'instituicao' => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'perfil_incompleto',
            'titulo' => 'Complete o seu perfil',
            'mensagem' => 'O seu acesso está limitado. Preencha os dados obrigatórios para continuar.',
            'url' => '/dashboard/settings/profile',
        ];
    }
}
