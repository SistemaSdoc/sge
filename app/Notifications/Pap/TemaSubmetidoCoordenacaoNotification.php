<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemaSubmetidoCoordenacaoNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap) {}

    public function via(object $notifiable): array
    {
        $canais = ['database'];

        if (! empty($notifiable->email)) {
            $canais[] = 'mail';
        }

        return $canais;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $turma = $this->grupoPap->turma;
        $turno = $turma->cursoClasseTurno;
        $classe = $turno->cursoClasse;
        $cursoTutelado = $classe->cursoTutelado;
        $instituicao = $cursoTutelado->instituicaoCurso->instituicao;

        return (new MailMessage)
            ->subject('Tema enviado para coordenação')
            ->view('mail.pap.tema-submetido-coordenacao', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'turma' => $turma->nome,
                'instituicao' => $instituicao,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tema_submetido_coordenacao',
            'titulo' => 'Tema enviado para coordenação',
            'mensagem' => "O tema \"{$this->grupoPap->tema_grupo}\" do grupo \"{$this->grupoPap->nome_grupo}\" foi submetido à coordenação e aguarda revisão.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => $this->urlGrupo(),
        ];
    }

    private function urlGrupo(): string
    {
        $turma = $this->grupoPap->turma;
        $turno = $turma->cursoClasseTurno;
        $classe = $turno->cursoClasse;
        $cursoTutelado = $classe->cursoTutelado;
        $instituicao = $cursoTutelado->instituicaoCurso->instituicao;

        return route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $classe->id,
            'cursoClasseTurno' => $turno->id,
            'turma' => $turma->id,
            'grupoPap' => $this->grupoPap->id,
        ]);
    }
}
