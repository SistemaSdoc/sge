<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataDefesaDefinidaNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Data de defesa definida')
            ->view('mail.pap.data-defesa-definida', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'dataDefesa' => $this->grupoPap->data_defesa->format('d/m/Y H:i'),
                'localDefesa' => $this->grupoPap->local_defesa,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'data_defesa_definida',
            'titulo' => 'Data de defesa definida',
            'mensagem' => "A defesa do grupo \"{$this->grupoPap->nome_grupo}\" está marcada para {$this->grupoPap->data_defesa->format('d/m/Y H:i')} em {$this->grupoPap->local_defesa}.",
            'grupo_pap_id' => $this->grupoPap->id,
            'data_defesa' => $this->grupoPap->data_defesa->toISOString(),
            'local_defesa' => $this->grupoPap->local_defesa,
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
