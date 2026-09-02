<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ProfessorCriadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $passwordPlain = '123456'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {

        $logoPath = $this->user->instituicao->logo
            ? base_path('storage/tenant' . tenant()->getTenantKey() . '/app/public/' . $this->user->instituicao->logo)
            : null;

        return (new MailMessage)
            ->subject('Conta de Professor criada')
            ->view('mail.professor.professor-criado', [
                'nome' => $this->user->nome,
                'email' => $this->user->email,
                'password' => $this->passwordPlain,
                'url' => route('tenant.login'),
                'instituicao' => $this->user->instituicao,
                'artigoInstituicao' => match ($this->user->instituicao->tipo) {
                    'instituto' => 'ao',
                    'colegio' => 'ao',
                    default => 'à',
                },
                'logoBase64' => $logoPath && file_exists($logoPath)
                    ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
                    : null,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'professor_criado',
            'titulo' => 'Conta criada com sucesso',
            'mensagem' => 'A sua conta de professor foi criada. Verifique o email para as suas credenciais.',
        ];
    }
}