<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JuradoAdicionadoBancaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public GrupoPap $grupoPap,
        public BancaJuriPap $bancaJuriPap
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Convocação para banca de júri PAP')
            ->view('mail.pap.jurado-adicionado-banca', [
                'nomeGrupo'   => $this->grupoPap->nome_grupo,
                'temaGrupo'   => $this->grupoPap->tema_grupo,
                'funcao'      => $this->bancaJuriPap->funcao,
                'dataDefesa'  => $this->grupoPap->data_defesa?->format('d/m/Y H:i') ?? 'Por definir',
                'localDefesa' => $this->grupoPap->local_defesa ?? 'Por definir',
                'url'         => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'         => 'jurado_adicionado_banca',
            'titulo'       => 'Convocado para banca de júri',
            'mensagem'     => "Foi convocado como {$this->bancaJuriPap->funcao} na banca do grupo \"{$this->grupoPap->nome_grupo}\".",
            'grupo_pap_id' => $this->grupoPap->id,
            'funcao'       => $this->bancaJuriPap->funcao,
            'url'          => $this->urlGrupo(),
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