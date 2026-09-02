<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemaValidadoPeloTutorNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $turma = $this->grupoPap->turma;
        $turno = $turma->cursoClasseTurno;
        $classe = $turno->cursoClasse;
        $cursoTutelado = $classe->cursoTutelado;
        $instituicao = $cursoTutelado->instituicaoCurso->instituicao;

        $url = route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $classe->id,
            'cursoClasseTurno' => $turno->id,
            'turma' => $turma->id,
            'grupoPap' => $this->grupoPap->id,
        ]);

        return (new MailMessage)
            ->subject('Tema PAP aguarda aprovação da coordenação')
            ->view('mail.pap.tema-validado-pelo-tutor', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'turma' => $turma->nome,
                'url' => $url,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tema_validado_tutor',
            'titulo' => 'Tema aguarda aprovação da coordenação',
            'mensagem' => "O tutor validou o tema \"{$this->grupoPap->tema_grupo}\" do grupo \"{$this->grupoPap->nome_grupo}\". Aguarda a sua aprovação.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => "/grupos-pap/{$this->grupoPap->id}",
        ];
    }
}