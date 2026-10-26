<?php

namespace App\Notifications;

use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordAlteradaNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public string $nomeUser,
        public string $email,
        public string $alteradoPor,
        public string $url,
        public ?string $password = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A sua palavra-passe foi alterada')
            ->view('mail.user.password-alterada-notification', [
                'nomeUser' => $this->nomeUser,
                'email' => $this->email,
                'alteradoPor' => $this->alteradoPor,
                'url' => $this->url,
                'password' => $this->password,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}