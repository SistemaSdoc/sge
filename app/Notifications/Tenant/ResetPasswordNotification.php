<?php

namespace App\Notifications\Tenant;

use App\Notifications\Concerns\ReliableNotification;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;
    use TenantNotificationUrl;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição da sua password')
            ->view('mail.tenant.reset-password', [
                'nome' => $notifiable->nome,
                'url' => $this->tenantNotificationUrl('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
