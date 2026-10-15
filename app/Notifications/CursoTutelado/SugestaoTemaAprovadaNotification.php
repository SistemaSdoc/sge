<?php

namespace App\Notifications\CursoTutelado;

use App\Models\Tenant\SugestaoTemaPap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugestaoTemaAprovadaNotification extends Notification
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
        $instituicao   = $cursoTutelado->instituicaoCurso->instituicao;

        $url = route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao'   => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);

        return (new MailMessage)
            ->subject('Sugestão de tema PAP aprovada')
            ->view('mail.curso-tutelado.aprovada', [
                'sugestao'    => $this->sugestao,
                'url'         => $url,
                'instituicao' => $instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'             => 'sugestao_tema_aprovada',
            'titulo'           => 'Sugestão de tema aprovada',
            'mensagem'         => "A tua sugestão de tema \"{$this->sugestao->titulo}\" foi aprovada.",
            'sugestao_tema_id' => $this->sugestao->id,
        ];
    }
}