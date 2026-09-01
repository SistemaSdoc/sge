<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrecaoSolicitadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public GrupoPap $grupoPap,
        public string $comentario,
        public string $solicitadoPor = 'tutor' // 'tutor' | 'coordenacao'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Correcção solicitada no trabalho PAP')
            ->view('mail.pap.correccao-solicitada', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'comentario' => $this->comentario,
                'solicitadoPor' => $this->solicitadoPor === 'tutor' ? 'professor tutor' : 'coordenação',
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'correccao_solicitada',
            'titulo' => 'Correcção solicitada',
            'mensagem' => "Foi solicitada uma correcção no trabalho do grupo \"{$this->grupoPap->nome_grupo}\". Consulte o feedback e reenvie.",
            'grupo_pap_id' => $this->grupoPap->id,
            'comentario' => $this->comentario,
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