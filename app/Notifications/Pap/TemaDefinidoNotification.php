<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemaDefinidoNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $professor = $this->grupoPap->professor?->user?->nome;

        return (new MailMessage)
            ->subject('Tema PAP definido')
            ->view('mail.pap.tema-definido', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'professorTutor' => $professor,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tema_definido',
            'titulo' => 'Tema definido',
            'mensagem' => "O tema \"{$this->grupoPap->tema_grupo}\" foi definido para o grupo \"{$this->grupoPap->nome_grupo}\".",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => $this->urlGrupo(),
        ];
    }

    private function urlGrupo(): string
    {
        $turma         = $this->grupoPap->turma;
        $turno         = $turma->cursoClasseTurno;
        $classe        = $turno->cursoClasse;
        $cursoTutelado = $classe->cursoTutelado;
        $instituicao   = $cursoTutelado->instituicaoCurso->instituicao;

        return route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao'      => $instituicao->id,
            'cursoTutelado'    => $cursoTutelado->id,
            'cursoClasse'      => $classe->id,
            'cursoClasseTurno' => $turno->id,
            'turma'            => $turma->id,
            'grupoPap'         => $this->grupoPap->id,
        ]);
    }
}
