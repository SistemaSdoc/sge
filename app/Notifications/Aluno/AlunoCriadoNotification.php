<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlunoCriadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $passwordPlain = '12345678'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conta de Aluno criada')
            ->view('mail.aluno.aluno-criado', [
                'nome'     => $this->user->nome,
                'email'    => $this->user->email,
                'password' => $this->passwordPlain,
                'url'      => route('tenant.login'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'aluno_criado',
            'titulo'   => 'Conta criada com sucesso',
            'mensagem' => 'A sua conta de aluno foi criada. Verifique o email para as suas credenciais.',
        ];
    }
}