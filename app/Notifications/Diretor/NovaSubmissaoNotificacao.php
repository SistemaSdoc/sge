<?php

namespace App\Notifications\Diretor;

use App\Models\Tenant\SubmissaoProva;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NovaSubmissaoNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public SubmissaoProva $submissao
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prazo = $this->submissao->prazo;

        return (new MailMessage)
            ->subject('Nova submissão de ' . ($this->submissao->professor?->user?->nome ?? 'Professor'))
            ->view('mail.diretor.nova-submissao', [
                'nome'          => $notifiable->nome,
                'professor'     => $this->submissao->professor?->user?->nome ?? 'Professor',
                'prazoTitulo'   => $prazo?->titulo,
                'disciplina'    => $prazo?->disciplina?->nome,
                'classe'        => $prazo?->classe?->nome,
                'turmaNome'     => $this->submissao->turma?->nome,
                'versao'        => $this->submissao->versao,
                'dataSubmissao' => $this->submissao->data_submissao?->format('d/m/Y H:i') ?? '—',
                'comentario'    => $this->submissao->comentario,
                'url'           => url("/dashboard/diretor/prazos/{$prazo?->id}/status"),
                'instituicao'   => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'nova_submissao',
            'titulo'   => 'Nova submissão recebida',
            'mensagem' => ($this->submissao->professor?->user?->nome ?? 'Professor') .
                          " submeteu a prova \"{$this->submissao->prazo?->titulo}\".",
            'url'      => "/dashboard/diretor/prazos/{$this->submissao->prazo_prova_id}/status",
        ];
    }
}