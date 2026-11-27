<?php

namespace App\Notifications\Aluno;

use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InscricaoEstadoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public string $nomeAluno,
        public string $estado,
        public ?string $nomeInstituicao,
        public string $artigoInstituicao,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(match ($this->estado) {
                'cancelada' => 'Cancelamento da matrícula',
                'reativada' => 'Reativação da matrícula',
                default => 'Estado da matrícula',
            })
            ->view('mail.aluno.matricula-confirmada', [
                'nome' => $this->nomeAluno,
                'estado' => $this->estado,
                'nomeInstituicao' => $this->nomeInstituicao ?? 'instituição',
                'artigoInstituicao' => $this->artigoInstituicao,
                'curso' => null,
                'classe' => null,
                'turno' => null,
                'nomeTurma' => null,
                'anoLectivo' => null,
            ]);
    }
}
