<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotaAtribuidaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public GrupoPap $grupoPap,
        public ElementoGrupoPap $elemento
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nota PAP atribuída')
            ->view('mail.pap.nota-atribuida', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'nota' => $this->elemento->nota_individual,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'nota_atribuida',
            'titulo' => 'Nota PAP atribuída',
            'mensagem' => "A tua nota individual do PAP foi atribuída: {$this->elemento->nota_individual} valores.",
            'grupo_pap_id' => $this->grupoPap->id,
            'nota' => $this->elemento->nota_individual,
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
