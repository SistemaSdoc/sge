<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Turma;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlunoTransferidoTurmaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Aluno $aluno,
        public Turma $turma
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Transferência de turma')
            ->view('mail.aluno.transferido-turma', [
                'nome'      => $this->aluno->inscricao?->candidato?->nome,
                'nomeTurma' => $this->turma->nome,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'aluno_transferido_turma',
            'titulo'   => 'Transferência de turma',
            'mensagem' => "Foi transferido para a turma \"{$this->turma->nome}\".",
        ];
    }
}