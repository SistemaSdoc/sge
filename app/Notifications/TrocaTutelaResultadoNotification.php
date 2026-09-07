<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informa o colégio sobre o resultado de uma decisão na troca de tutela.
 */
class TrocaTutelaResultadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $instituicaoDecisora,
        public string $instituicaoProposta,
        public string $cursoNome,
        public string $sharedId,
        public string $resultado,
        public string $fase,
        public string $url = '',
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filter_var($notifiable->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $aprovada = $this->resultado === 'aprovada';
        $mensagem = $this->resultado === 'pendente'
            ? "A troca de tutela do curso {$this->cursoNome} para {$this->instituicaoProposta} foi registada e aguarda a aprovação da instituição tutora actual, {$this->instituicaoDecisora}."
            : ($this->fase === 'instituicao_anterior'
            ? "O {$this->instituicaoDecisora} aprovou a troca de tutela do curso {$this->cursoNome} para o {$this->instituicaoProposta}. A instituição proposta ainda precisa aceitar a tutela."
            : ($aprovada
                ? "O {$this->instituicaoDecisora} aceitou a tutela do curso {$this->cursoNome}. A troca foi concluída."
                : "O {$this->instituicaoDecisora} rejeitou assumir a tutela do curso {$this->cursoNome}. A tutela anterior permanece activa."));

        return [
            'tipo' => 'troca_tutela_resultado',
            'titulo' => $this->resultado === 'pendente'
                ? 'Troca de tutela pendente'
                : ($this->fase === 'instituicao_anterior'
                ? 'Troca de tutela aprovada pela instituição actual'
                : ($aprovada ? 'Tutela aceite' : 'Tutela rejeitada')),
            'mensagem' => $mensagem,
            'curso_nome' => $this->cursoNome,
            'instituicao_decisora' => $this->instituicaoDecisora,
            'instituicao_proposta' => $this->instituicaoProposta,
            'curso_tutelado_shared_id' => $this->sharedId,
            'resultado' => $this->resultado,
            'fase' => $this->fase,
            'status' => $this->resultado,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaultUrl = config('app.url').'/dashboard';
        $finalUrl = $this->url ?: $defaultUrl;

        return (new MailMessage)
            ->subject($this->resultado === 'pendente'
                ? 'Troca de tutela pendente'
                : ($this->resultado === 'aprovada' ? 'Actualização da troca de tutela' : 'Tutela rejeitada'))
            ->markdown('mail.troca-tutela-resultado-notification', [
                'nomeInstituicaoDecisora' => $this->instituicaoDecisora,
                'nomeInstituicaoProposta' => $this->instituicaoProposta,
                'nomeCurso' => $this->cursoNome,
                'resultado' => $this->resultado,
                'fase' => $this->fase,
                'url' => $finalUrl,
            ]);
    }
}
