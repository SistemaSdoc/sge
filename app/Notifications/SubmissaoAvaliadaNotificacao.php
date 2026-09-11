<?php

namespace App\Notifications;

use App\Models\SubmissaoProva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissaoAvaliadaNotificacao extends Notification
{
    use Queueable;

    public function __construct(
        public SubmissaoProva $submissao
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prazo = $this->submissao->prazo;
        $aprovado = $this->submissao->estado === 'aprovado';
        $icone = $aprovado ? '✅' : '❌';
        $resultado = $aprovado ? 'APROVADA' : 'REJEITADA';

        $mail = (new MailMessage)
            ->subject("{$icone} Submissão {$resultado}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line("A sua submissão foi **{$resultado}** pelo diretor.")
            ->line('**Detalhes:**')
            ->line("• Prazo: {$prazo?->titulo}")
            ->line("• Disciplina: {$prazo?->disciplina?->nome }")
            ->line("• Classe: {$prazo?->classe?->nome }")
            ->line("• Turma: {$this->submissao->turma?->nome}")
            ->line("• Versão: {$this->submissao->versao}")
            ->line("• Data: {$this->submissao->data_submissao->format('d/m/Y H:i')}");

        if ($this->submissao->parecer_diretor) {
            $mail->line('')
                 ->line('**Parecer do diretor:**')
                 ->line("_{$this->submissao->parecer_diretor}_");
        }

        if (!$aprovado) {
            $mail->line('')
                 ->line('💡 Pode submeter uma nova versão enquanto o prazo estiver aberto.');
        }

        return $mail
            ->action('Ver no Dashboard', url('/dashboard/professor/provas'))
            ->line('Obrigado!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'              => 'submissao_avaliada',
            'submissao_id'      => $this->submissao->id,
            'prazo_id'          => $this->submissao->prazo_prova_id,
            'estado'            => $this->submissao->estado,
            'estado_label'      => $this->submissao->estado_label,
            'parecer_diretor'   => $this->submissao->parecer_diretor,
            'versao'            => $this->submissao->versao,
            'prazo_titulo'      => $this->submissao->prazo?->titulo,
            'disciplina'        => $this->submissao->prazo?->disciplina?->nome,
            'classe'            => $this->submissao->prazo?->classe?->nome,
            'turma'             => $this->submissao->turma?->nome,
            'data'              => $this->submissao->data_submissao->format('d/m/Y H:i'),
            'url'               => '/dashboard/professor/provas',
        ];
    }
}