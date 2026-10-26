<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessorAtribuidoADisciplinaNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public Professor $professor,
        public Turma $turma,
        public ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $instituicao = $this->professor->user->instituicao;

        return (new MailMessage)
            ->subject('Atribuído a uma disciplina')
            ->view('mail.professor.atribuido-a-disciplina', [
                'nome' => $this->professor->user->nome,
                'nomeDisciplina' => $this->classeTurnoDisciplina->disciplina?->nome,
                'nomeTurma' => $this->turma->nome,
                'instituicao' => $instituicao,
                'artigoInstituicao' => match ($instituicao->tipo) {
                    'instituto', 'colegio' => 'ao',
                    default => 'à',
                },
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'professor_atribuido_disciplina',
            'titulo' => 'Atribuído a uma disciplina',
            'mensagem' => "Foi atribuído à disciplina \"{$this->classeTurnoDisciplina->disciplina?->nome}\" na turma \"{$this->turma->nome}\".",
        ];
    }
}
