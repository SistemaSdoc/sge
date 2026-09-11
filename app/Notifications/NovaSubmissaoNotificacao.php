<?php

namespace App\Notifications;

use App\Models\SubmissaoProva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NovaSubmissaoNotificacao extends Notification
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
        $professor = $this->submissao->professor?->user?->nome ?? 'Professor';
        $turma = $this->submissao->turma?->nome ?? 'N/A';

        return (new MailMessage)
            ->subject("Nova submissão: {$professor}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line("O professor **{$professor}** submeteu uma prova. Está pendente de avaliação.")
            ->line('**Detalhes:**')
            ->line("• Prazo: {$prazo?->titulo}")
            ->line("• Disciplina: {$prazo?->disciplina?->nome }")
            ->line("• Classe: {$prazo?->classe?->nome}")
            ->line("• Turma: {$turma}")
            ->line("• Versão: {$this->submissao->versao}")
            ->line("• Data: {$this->submissao->data_submissao->format('d/m/Y H:i')}")
            ->action('Avaliar Submissão', url("/dashboard/diretor/prazos/{$prazo?->id}/status"))
            ->line('Aguarda a sua avaliação.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'          => 'nova_submissao',
            'submissao_id'  => $this->submissao->id,
            'prazo_id'      => $this->submissao->prazo_prova_id,
            'professor_nome'=> $this->submissao->professor?->user?->nome ?? 'N/A',
            'prazo_titulo'  => $this->submissao->prazo?->titulo,
            'disciplina'    => $this->submissao->prazo?->disciplina?->nome,
            'classe'        => $this->submissao->prazo?->classe?->nome,
            'turma'         => $this->submissao->turma?->nome,
            'versao'        => $this->submissao->versao,
            'data'          => $this->submissao->data_submissao->format('d/m/Y H:i'),
            'url'           => "/dashboard/diretor/prazos/{$this->submissao->prazo_prova_id}/status",
        ];
    }
}