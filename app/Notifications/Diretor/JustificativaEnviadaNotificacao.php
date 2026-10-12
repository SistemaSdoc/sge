<?php

namespace App\Notifications\Diretor;

use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JustificativaEnviadaNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public JustificativaNaoSubmissao $justificativa
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prazo = $this->justificativa->prazo;
        $professor = $this->justificativa->professor?->user?->nome ?? 'Professor';

        return (new MailMessage)
            ->subject('Nova justificativa de '.$professor)
            ->view('mail.diretor.justificativa-enviada', [
                'nome' => $notifiable->nome,
                'professor' => $professor,
                'prazoTitulo' => $prazo?->titulo,
                'disciplina' => $prazo?->disciplina?->nome,
                'classe' => $prazo?->classe?->nome,
                'turmaNome' => $this->justificativa->turma?->nome,
                'dataJustificativa' => $this->justificativa->data_justificativa?->format('d/m/Y H:i') ?? '—',
                'motivo' => $this->justificativa->motivo,
                'url' => url("/dashboard/diretor/prazos/{$prazo?->id}/status"),
                'instituicao' => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $professor = $this->justificativa->professor?->user?->nome ?? 'Professor';

        return [
            'tipo' => 'justificativa_enviada',
            'titulo' => 'Nova justificativa recebida',
            'mensagem' => "{$professor} enviou uma justificativa para \"{$this->justificativa->prazo?->titulo}\".",
            'url' => "/dashboard/diretor/prazos/{$this->justificativa->prazo_prova_id}/status",
        ];
    }
}
