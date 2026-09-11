<?php

namespace App\Notifications\Pap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrabalhoSubmetidoConfirmacaoNotification extends Notification
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
            ->subject('Trabalho recebido pela coordenação')
            ->view('mail.pap.trabalho-submetido-confirmacao', [
                'nomeGrupo' => $this->grupoPap->nome_grupo,
                'turma' => $turma->nome,
                'instituicao' => $instituicao,
                'url' => $this->urlGrupo(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'trabalho_submetido_confirmacao',
            'titulo' => 'Trabalho recebido pela coordenação',
            'mensagem' => "O trabalho do grupo \"{$this->grupoPap->nome_grupo}\" foi submetido com sucesso e está em análise. Aguarda revisão da coordenação.",
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
