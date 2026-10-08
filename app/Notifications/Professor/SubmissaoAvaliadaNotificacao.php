<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\SubmissaoProva;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissaoAvaliadaNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        $aprovado = $this->submissao->estado === 'aprovado';
        $prazo = $this->submissao->prazo;

        return (new MailMessage)
            ->subject($aprovado ? 'Submissão aprovada' : 'Submissão rejeitada')
            ->view('mail.professor.submissao-avaliada', [
                'nome'           => $notifiable->nome,
                'aprovado'       => $aprovado,
                'prazoTitulo'    => $prazo?->titulo,
                'disciplina'     => $prazo?->disciplina?->nome,
                'classe'         => $prazo?->classe?->nome,
                'turmaNome'      => $this->submissao->turma?->nome,
                'versao'         => $this->submissao->versao,
                'dataSubmissao'  => $this->submissao->data_submissao?->format('d/m/Y H:i') ?? '—',
                'parecer'        => $this->submissao->parecer_diretor,
                'url'            => url('/dashboard/professor/provas'),
                'instituicao'    => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $aprovado = $this->submissao->estado === 'aprovado';

        return [
            'tipo'     => 'submissao_avaliada',
            'titulo'   => $aprovado ? 'Submissão aprovada' : 'Submissão rejeitada',
            'mensagem' => "A sua submissão para \"{$this->submissao->prazo?->titulo}\" foi " . ($aprovado ? 'aprovada' : 'rejeitada') . ".",
            'url'      => '/dashboard/professor/provas',
        ];
    }
}