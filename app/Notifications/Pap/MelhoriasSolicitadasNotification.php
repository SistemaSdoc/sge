<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MelhoriasSolicitadasNotification extends Notification
{
    use Queueable;

    public function __construct(
        public GrupoPap $grupoPap,
        public string $solicitadoPor // 'tutor' | 'coordenacao'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quem = $this->solicitadoPor === 'tutor' ? 'tutor' : 'coordenação';

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
            ->subject('Melhorias solicitadas no tema PAP')
            ->view('mail.pap.melhorias-solicitadas', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'temaGrupo' => $this->grupoPap->tema_grupo,
                'comentario' => $this->grupoPap->comentario_aprovacao,
                'solicitadoPor' => $quem,
                'url' => $url,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $quem = $this->solicitadoPor === 'tutor' ? 'tutor' : 'coordenação';

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

        return [
            'tipo' => 'melhorias_solicitadas',
            'titulo' => 'Melhorias solicitadas no tema',
            'mensagem' => "O {$quem} solicitou melhorias no tema \"{$this->grupoPap->tema_grupo}\". Consulte o feedback e reenvie.",
            'grupo_pap_id' => $this->grupoPap->id,
            'solicitado_por' => $this->solicitadoPor,
            'url' => $url,
        ];
    }
}