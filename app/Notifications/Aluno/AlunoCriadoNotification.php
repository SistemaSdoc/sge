<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\User;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class AlunoCriadoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

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
        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk(config('filesystems.default'));
        $logoUrl = $this->user->instituicao->logo
            ? $publicDisk->url($this->user->instituicao->logo)
            : null;

        return (new MailMessage)
            ->subject('Conta de Aluno criada')
            ->view('mail.aluno.aluno-criado', [
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
                'logoUrl' => $logoUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'aluno_criado',
            'titulo' => 'Conta criada com sucesso',
            'mensagem' => 'A sua conta de aluno foi criada. Verifique o email para as suas credenciais.',
        ];
    }
}
