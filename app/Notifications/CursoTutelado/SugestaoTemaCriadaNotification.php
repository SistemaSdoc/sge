<?php

namespace App\Notifications\CursoTutelado;

use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugestaoTemaCriadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SugestaoTemaPap $sugestao,
        public User $criadoPor,
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
            ->subject('Nova sugestão de tema PAP submetida')
            ->view('mail.curso-tutelado.criada', [
                'sugestao' => $this->sugestao,
                'criadoPor' => $this->criadoPor,
                'url' => $url,
                'instituicao' => $instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'sugestao_tema_criada',
            'titulo' => 'Nova sugestão de tema PAP',
            'mensagem' => "Uma nova sugestão de tema foi submetida: \"{$this->sugestao->titulo}\".",
            'sugestao_tema_id' => $this->sugestao->id,
        ];
    }
}
