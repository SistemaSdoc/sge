<?php

namespace App\Notifications\CursoTutelado;

use App\Models\Tenant\SugestaoTemaPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugestaoTemaMelhoriaSolicitadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SugestaoTemaPap $sugestao,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cursoTutelado = $this->sugestao->cursoTutelado;
        $instituicao = $cursoTutelado->instituicaoCurso->instituicao;

        $url = route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);

        return (new MailMessage)
            ->subject('Melhoria solicitada na sugestão de tema PAP')
            ->view('mail.curso-tutelado.melhoria-solicitada', [
                'sugestao' => $this->sugestao,
                'observacao' => $this->sugestao->observacao_aprovacao,
                'url' => $url,
                'instituicao' => $instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'sugestao_tema_melhoria_solicitada',
            'titulo' => 'Melhoria solicitada na sugestão de tema',
            'mensagem' => "Foi solicitada uma melhoria na tua sugestão de tema \"{$this->sugestao->titulo}\".",
            'sugestao_tema_id' => $this->sugestao->id,
        ];
    }
}
