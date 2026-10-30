<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlunoAdicionadoAoGrupoNotification extends Notification
{
    use ReliableNotification;

    public function __construct(
        public GrupoPap $grupoPap,
        public string $nomeAluno,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Adicionado a um grupo PAP')
            ->view('mail.pap.aluno-adicionado-ao-grupo', [
                'nomeAluno' => $this->nomeAluno,
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'url'       => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'        => 'aluno_adicionado_ao_grupo',
            'titulo'      => 'Adicionado a um grupo PAP',
            'mensagem'    => "Foste adicionado ao grupo PAP \"{$this->grupoPap->nome_grupo}\".",
            'grupo_pap_id' => $this->grupoPap->id,
            'url'         => $this->urlGrupo(),
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