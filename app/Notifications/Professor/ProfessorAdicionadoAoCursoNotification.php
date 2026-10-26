<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Professor;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessorAdicionadoAoCursoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public Professor $professor,
        public CursoTutelado $cursoTutelado,
        public ?CursoTuteladoProfessor $vinculo = null
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
                'papeis' => $this->papeis(),
                'artigoInstituicao' => match ($instituicao->tipo) {
                    'instituto', 'colegio' => 'ao',
                    default => 'à',
                },
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $papeis = $this->papeis();

        return [
            'tipo' => 'professor_adicionado_curso',
            'titulo' => 'Adicionado a um curso',
            'mensagem' => "Foi adicionado ao curso \"{$this->cursoTutelado->instituicaoCurso?->curso?->nome}\" com os papéis: ".implode(', ', $papeis).'.',
            'papeis' => $papeis,
        ];
    }

    // public function toBroadcast(object $notifiable): BroadcastMessage
    // {
    //     return new BroadcastMessage([
    //         'titulo' => 'Adicionado a um curso',
    //         'mensagem' => "Foi adicionado ao curso \"{$this->cursoTutelado->instituicaoCurso?->curso?->nome}\".",
    //     ]);
    // }
    private function papeis(): array
    {
        if ($this->vinculo === null) {
            return ['Professor'];
        }

        $papeis = [match ($this->vinculo->tipo) {
            'principal' => 'Professor principal',
            'colaborador' => 'Professor colaborador',
            default => 'Professor',
        }];

        if ((bool) $this->vinculo->coordenador) {
            $papeis[] = 'Coordenador do curso';
        }

        if ((bool) $this->vinculo->opap) {
            $papeis[] = 'OPAP';
        }

        $papelGrupoDisciplinar = match ($this->vinculo->grupo_disciplinar) {
            'membro' => 'Membro do Grupo Disciplinar',
            'coordenador' => 'Coordenador do Grupo Disciplinar',
            default => null,
        };

        if ($papelGrupoDisciplinar !== null) {
            $papeis[] = $papelGrupoDisciplinar;
        }

        return $papeis;
    }
}
