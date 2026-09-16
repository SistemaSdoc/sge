<?php

namespace App\Notifications\User;

use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class UserCriadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $passwordPlain,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conta criada com sucesso')
            ->view('mail.user.user-criado', [
                'nome' => $this->user->nome,
                'email' => $this->user->email,
                'password' => $this->passwordPlain,
                'url' => route('tenant.login'),
                'instituicao' => $this->user->instituicao,
                'artigoInstituicao' => match ($this->user->instituicao?->tipo ?? 'instituto') {
                    'instituto' => 'ao',
                    'colegio' => 'ao',
                    default => 'à',
                },
                'logoUrl' => $this->user->instituicao?->logo
                    ? Storage::url($this->user->instituicao->logo)
                    : null,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'usuario_criado',
            'titulo' => 'Conta criada com sucesso',
            'mensagem' => 'A sua conta foi criada. Verifique o email para as suas credenciais.',
        ];
    }
}
