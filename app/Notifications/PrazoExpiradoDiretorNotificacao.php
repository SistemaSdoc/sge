<?php

namespace App\Notifications;

use App\Models\PrazoProva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoExpiradoDiretorNotificacao extends Notification
{
    use Queueable;

    public function __construct(
        public PrazoProva $prazo,
        public int $totalProfessores,
        public int $submeteram,
        public int $naoSubmeteram,
        public int $justificaram
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(" Prazo expirado: {$this->tituloPrazo()}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line("O prazo abaixo expirou. Segue o resumo de cumprimento:")
            ->line("**Detalhes do prazo:**")
            ->line("• Título: {$this->tituloPrazo()}")
            ->line("• Disciplina: {$this->prazo->disciplina?->nome }")
            ->line("• Classe: {$this->prazo->classe?->nome }")
            ->line("• Data Limite: {$this->prazo->data_limite->format('d/m/Y H:i')}")
            ->line("• Período: {$this->prazo->periodo}")
            ->line('')
            ->line('**Estatísticas:**')
            ->line("• Total de professores: {$this->totalProfessores}")
            ->line("•  Submeteram: {$this->submeteram}")
            ->line("•  Não submeteram: {$this->naoSubmeteram}")
            ->line("•  Justificaram: {$this->justificaram}")
            ->action('Ver Detalhes', url("/dashboard/diretor/prazos/{$this->prazo->id}/status"))
            ->line('Obrigado!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'             => 'prazo_expirado',
            'prazo_id'         => $this->prazo->id,
            'titulo'           => $this->tituloPrazo(),
            'disciplina'       => $this->prazo->disciplina?->nome,
            'classe'           => $this->prazo->classe?->nome,
            'data_limite'      => $this->prazo->data_limite->format('d/m/Y H:i'),
            'total_professores'=> $this->totalProfessores,
            'submeteram'       => $this->submeteram,
            'nao_submeteram'   => $this->naoSubmeteram,
            'justificaram'     => $this->justificaram,
            'url'              => "/dashboard/diretor/prazos/{$this->prazo->id}/status",
        ];
    }

    private function tituloPrazo(): string
    {
        return $this->prazo->titulo ?? $this->prazo->tipo_prova;
    }
}