<?php

namespace App\Notifications\Aluno;

use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerfilIncompletoNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ação necessária: complete o seu perfil')
            ->view('mail.aluno.perfil-incompleto', [
                'nome'        => $notifiable->nome,
                'url'         => url('/dashboard/settings/profile'),
                'instituicao' => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'perfil_incompleto',
            'titulo'   => 'Complete o seu perfil',
            'mensagem' => 'O seu acesso está limitado. Preencha os dados obrigatórios para continuar.',
            'url'      => '/dashboard/settings/profile',
        ];
    }
}