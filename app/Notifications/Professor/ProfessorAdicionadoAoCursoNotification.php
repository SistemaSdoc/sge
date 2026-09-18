<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Professor;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessorAdicionadoAoCursoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public Professor $professor,
        public CursoTutelado $cursoTutelado
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $instituicao = $this->professor->user->instituicao;

        return (new MailMessage)
            ->subject('Adicionado a um curso')
            ->view('mail.professor.adicionado-ao-curso', [
                'nome' => $this->professor->user->nome,
                'nomeCurso' => $this->cursoTutelado->instituicaoCurso?->curso?->nome,
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
            'tipo' => 'professor_adicionado_curso',
            'titulo' => 'Adicionado a um curso',
            'mensagem' => "Foi adicionado ao curso \"{$this->cursoTutelado->instituicaoCurso?->curso?->nome}\".",
        ];
    }
}
