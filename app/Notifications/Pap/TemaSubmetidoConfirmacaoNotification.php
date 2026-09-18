<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemaSubmetidoConfirmacaoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(public GrupoPap $grupoPap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tema PAP submetido ao tutor!')
            ->view('mail.pap.tema-submetido-confirmacao', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tema_submetido_confirmacao',
            'titulo' => 'Tema submetido!',
            'mensagem' => "O tema \"{$this->grupoPap->tema_grupo}\" do grupo \"{$this->grupoPap->nome_grupo}\" foi entregue ao tutor com sucesso.",
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
