<?php

namespace App\Notifications\Tenant;

use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotesAccessCodeNotification extends Notification implements ShouldQueue
{
    use Queueable, ReliableNotification;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de confirmação para acesso às notas')
            ->view('mail.tenant.notes-access-code', [
                'nome' => $notifiable->nome,
                'code' => $this->code,
            ]);
    }
}
