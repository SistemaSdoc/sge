<?php

namespace App\Notifications;

use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JustificativaAvaliadaNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        $aceita = $this->status() === 'aceita';
        $prazo = $this->justificativa->prazo;

        return (new MailMessage)
            ->subject($aceita ? 'Justificativa aceite' : 'Justificativa recusada')
            ->view('mail.professor.justificativa-avaliada', [
                'nome' => $notifiable->nome,
                'aceita' => $aceita,
                'statusLabel' => $this->statusLabel(),
                'prazoTitulo' => $prazo?->titulo,
                'disciplina' => $prazo?->disciplina?->nome,
                'classe' => $prazo?->classe?->nome,
                'turmaNome' => $this->justificativa->turma?->nome,
                'dataAvaliacao' => $this->justificativa->updated_at?->format('d/m/Y H:i') ?? '—',
                'motivo' => $this->justificativa->motivo,
                'parecer' => $this->justificativa->parecer_diretor,
                'url' => url('/dashboard/professor/provas'),
                'instituicao' => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $aceita = $this->status() === 'aceita';

        return [
            'tipo' => 'justificativa_avaliada',
            'titulo' => $aceita ? 'Justificativa aceite' : 'Justificativa recusada',
            'mensagem' => "A sua justificativa para \"{$this->justificativa->prazo?->titulo}\" foi {$this->statusLabel()}.",
            'url' => '/dashboard/professor/provas',
        ];
    }

    private function status(): string
    {
        return $this->justificativa->status
            ?? $this->justificativa->estado
            ?? ($this->justificativa->parecer_diretor ? 'aceita' : 'recusada');
    }

    private function statusLabel(): string
    {
        return match ($this->status()) {
            'aceita' => 'aceite',
            'recusada' => 'recusada',
            default => $this->status(),
        };
    }
}
