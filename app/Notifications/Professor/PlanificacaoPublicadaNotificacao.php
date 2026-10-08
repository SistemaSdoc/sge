<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\Planificacao;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanificacaoPublicadaNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public Planificacao $planificacao,
        public int $versao,
        public bool $primeiraVersao = true,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                $this->primeiraVersao
                    ? 'Planificação disponível: ' . $this->nomeDisciplina()
                    : 'Planificação atualizada: ' . $this->nomeDisciplina()
            )
            ->view('mail.planificacao.publicada', [
                'nome'         => $notifiable->nome,
                'titulo'       => $this->planificacao->titulo ?? 'Planificação',
                'disciplina'   => $this->nomeDisciplina(),
                'classe'       => $this->nomeClasse(),
                'periodo'      => $this->planificacao->periodo,
                'anoLetivo'    => $this->nomeAnoLetivo(),
                'versao'       => $this->versao,
                'primeira'     => $this->primeiraVersao,
                'url'          => url("/dashboard/planificacoes/{$this->planificacao->id}"),
                'instituicao'  => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'planificacao_publicada',
            'titulo'   => $this->primeiraVersao
                ? 'Nova planificação disponível'
                : 'Planificação atualizada',
            'mensagem' => sprintf(
                '%s — %s (%s) • v%d',
                $this->nomeDisciplina(),
                $this->nomeClasse(),
                $this->planificacao->periodo,
                $this->versao
            ),
            'url'      => "/dashboard/planificacoes/{$this->planificacao->id}",
        ];
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function nomeDisciplina(): string
    {
        return $this->planificacao->disciplina?->nome ?? 'Disciplina';
    }

    private function nomeClasse(): string
    {
        return $this->planificacao->classe?->nome ?? 'Classe';
    }

    private function nomeAnoLetivo(): string
    {
        return $this->planificacao->anoLectivo?->nome ?? '—';
    }
}