<?php

namespace App\Notifications;

use App\Models\Tenant\JustificativaNaoSubmissao;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JustificativaAvaliadaNotificacao extends Notification
{
    use Queueable;

    public function __construct(
        public JustificativaNaoSubmissao $justificativa
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prazo = $this->justificativa->prazo;
        $aceita = $this->status() === 'aceita';
        $icone = $aceita ? ' ' : ' ';
        $resultado = $aceita ? 'ACEITE' : 'RECUSADA';

        $mail = (new MailMessage)
            ->subject("{$icone} Justificativa {$resultado}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line("A sua justificativa de não submissão foi **{$resultado}** pelo diretor.")
            ->line('**Detalhes:**')
            ->line("• Prazo: {$prazo?->titulo}")
            ->line("• Disciplina: {$prazo?->disciplina?->nome}")
            ->line("• Classe: {$prazo?->classe?->nome}")
            ->line("• Data da justificativa: " . ($this->justificativa->data_justificativa?->format('d/m/Y H:i') ?? '—'))
            ->line('')
            ->line('**Motivo apresentado:**')
            ->line("_{$this->justificativa->motivo}_");

        if ($this->justificativa->parecer_diretor) {
            $mail->line('')
                 ->line('**Parecer do diretor:**')
                 ->line("_{$this->justificativa->parecer_diretor}_");
        }

        if (! $aceita) {
            $mail->line('')
                 ->line('💡 Se o prazo ainda estiver aberto, pode submeter uma nova justificativa.');
        }

        return $mail
            ->action('Ver no Dashboard', url('/dashboard/professor/provas'))
            ->line('Obrigado!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'             => 'justificativa_avaliada',
            'justificativa_id' => $this->justificativa->id,
            'prazo_id'         => $this->justificativa->prazo_prova_id,
            'status'           => $this->status(),
            'status_label'     => $this->statusLabel(),
            'parecer_diretor'  => $this->justificativa->parecer_diretor,
            'motivo'           => $this->justificativa->motivo,
            'prazo_titulo'     => $this->justificativa->prazo?->titulo,
            'disciplina'       => $this->justificativa->prazo?->disciplina?->nome,
            'classe'           => $this->justificativa->prazo?->classe?->nome,
            'data'             => $this->justificativa->data_justificativa?->format('d/m/Y H:i'),
            'url'              => '/dashboard/professor/provas',
        ];
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function status(): string
    {
        // Tenta vários campos possíveis do model
        return $this->justificativa->status
            ?? $this->justificativa->estado
            ?? ($this->justificativa->parecer_diretor ? 'aceita' : 'recusada');
    }

    private function statusLabel(): string
    {
        return match ($this->status()) {
            'aceita'   => 'Aceite',
            'recusada' => 'Recusada',
            default    => ucfirst($this->status()),
        };
    }
}