<?php

namespace App\Notifications\Diretor;

use App\Models\Tenant\PrazoProva;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoExpiradoDiretorNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public PrazoProva $prazo,
        public int $totalProfessores,
        public int $submeteram,
        public int $naoSubmeteram,
        public int $justificaram,
        public array $atribuicoes = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Prazo expirado: ' . $this->tituloPrazo())
            ->view('mail.diretor.prazo-expirado', [
                'nome'             => $notifiable->nome,
                'titulo'           => $this->tituloPrazo(),
                'disciplina'       => $this->prazo->disciplina?->nome,
                'classe'           => $this->prazo->classe?->nome,
                'dataLimite'       => $this->prazo->data_limite?->format('d/m/Y H:i') ?? '—',
                'periodo'          => $this->prazo->periodo,
                'totalProfessores' => $this->totalProfessores,
                'submeteram'       => $this->submeteram,
                'naoSubmeteram'    => $this->naoSubmeteram,
                'justificaram'     => $this->justificaram,
                'taxaCumprimento'  => $this->taxaCumprimento(),
                'atribuicoes'      => $this->atribuicoes,
                'url'              => url("/dashboard/diretor/prazos/{$this->prazo->id}/status"),
                'instituicao'      => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'prazo_expirado',
            'titulo'   => 'Prazo expirado',
            'mensagem' => "O prazo \"{$this->tituloPrazo()}\" expirou. "
                          . "{$this->submeteram}/{$this->totalProfessores} professores submeteram.",
            'url'      => "/dashboard/diretor/prazos/{$this->prazo->id}/status",
        ];
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function tituloPrazo(): string
    {
        return $this->prazo->titulo ?? $this->prazo->tipo_prova ?? 'Prazo';
    }

    private function taxaCumprimento(): int
    {
        if ($this->totalProfessores === 0) {
            return 0;
        }

        return (int) round(($this->submeteram / $this->totalProfessores) * 100);
    }
}