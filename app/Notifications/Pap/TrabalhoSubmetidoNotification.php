<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrabalhoSubmetidoNotification extends Notification
{
    use Queueable;

    public function __construct(public GrupoPap $grupoPap,  public ?string $urlExterno = null,)
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

        $url = $this->urlExterno ?? route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $classe->id,
            'cursoClasseTurno' => $turno->id,
            'turma' => $turma->id,
            'grupoPap' => $this->grupoPap->id,
        ]);

        return (new MailMessage)
            ->subject('Novo trabalho submetido')
            ->view('mail.pap.trabalho-submetido', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'instituicao' => $instituicao,
                'artigoInstituicao' => match ($instituicao->tipo) {
                    'instituto', 'colegio' => 'ao',
                    default => 'à',
                },
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
            'tipo' => 'trabalho_submetido',
            'titulo' => 'Novo trabalho submetido',
            'mensagem' => "O grupo \"{$this->grupoPap->nome_grupo}\" do {$nomeInstituicao} submeteu uma nova versão do trabalho PAP para revisão.",
            'grupo_pap_id' => $this->grupoPap->id,
            'url' => $this->urlExterno ?? "/grupos-pap/{$this->grupoPap->id}",
        ];
    }
}
