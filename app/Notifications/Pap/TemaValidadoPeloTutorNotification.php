<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemaValidadoPeloTutorNotification extends Notification
{
    use Queueable;
    use TenantNotificationUrl;

    public function __construct(
        public GrupoPap $grupoPap,
        public ?string $url = null,
    ) {}

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

        $url = $this->url ?? $this->tenantNotificationUrl('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
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
                'nomeInstituicao' => $instituicao->nome,
                'url' => $url,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $cursoTutelado = $this->grupoPap->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado;
        $nomeInstituicao = $cursoTutelado?->instituicaoCurso?->instituicao?->nome;

        return [
            'tipo' => 'tema_validado_tutor',
            'titulo' => 'Tema aguarda aprovação da coordenação',
            'mensagem' => "O tutor validou o tema \"{$this->grupoPap->tema_grupo}\" do grupo \"{$this->grupoPap->nome_grupo}\" do {$nomeInstituicao}. Aguarda a sua aprovação.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => $this->url ?? "/grupos-pap/{$this->grupoPap->id}",
        ];
    }
}
