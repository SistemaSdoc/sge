<?php

namespace App\Notifications\User;

use App\Models\Central\User as CentralUser;
use App\Models\Tenant\User as TenantUser;
use App\Notifications\Concerns\ReliableNotification;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class EmailAlteradoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;
    use TenantNotificationUrl;

    public function __construct(
        public CentralUser|TenantUser $user,
        public string $passwordPlain,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->user instanceof CentralUser
            ? route('central.login')
            : $this->tenantNotificationUrl('tenant.login');

        $instituicao = $this->user instanceof TenantUser
            ? $this->user->instituicao
            : null;

        return (new MailMessage)
            ->subject('Dados de acesso actualizados')
            ->view('mail.user.user-criado', [
                'nome' => $this->user->nome,
                'email' => $this->user->email,
                'password' => $this->passwordPlain,
                'url' => $url,
                'instituicao' => $instituicao,
                'artigoInstituicao' => match ($instituicao?->tipo ?? 'instituto') {
                    'instituto', 'colegio' => 'ao',
                    default => 'à',
                },
                'logoUrl' => $instituicao?->logo
                    ? Storage::url($instituicao->logo)
                    : null,
                'dadosActualizados' => true,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'email_alterado',
            'titulo' => 'Endereço de email alterado',
            'mensagem' => 'O seu endereço de email foi alterado. Use o novo email para aceder à plataforma.',
        ];
    }
}
