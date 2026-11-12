<?php

namespace App\Notifications\CursoTutelado;

use App\Models\Tenant\SugestaoTemaPap;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugestaoTemaRejeitadaNotification extends Notification
{
    use Queueable;
    use TenantNotificationUrl;

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

        $url = $this->tenantNotificationUrl('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);

        return (new MailMessage)
            ->subject('Sugestão de tema PAP rejeitada')
            ->view('mail.curso-tutelado.rejeitada', [
                'sugestao' => $this->sugestao,
                'observacao' => $this->sugestao->observacao_aprovacao,
                'url' => $url,
                'instituicao' => $instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'sugestao_tema_rejeitada',
            'titulo' => 'Sugestão de tema rejeitada',
            'mensagem' => "A tua sugestão de tema \"{$this->sugestao->titulo}\" foi rejeitada.",
            'sugestao_tema_id' => $this->sugestao->id,
        ];
    }
}
