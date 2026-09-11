<?php

namespace App\Notifications\Pauta;

use App\Models\Tenant\SolicitacaoEdicaoPauta;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitacaoEdicaoPautaDirectorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SolicitacaoEdicaoPauta $solicitacao,
        public User $director
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $solicitacao = $this->solicitacao->load([
            'professor',
            'turmaDisciplinaProfessor.turma',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        $tipoLabel = match ($solicitacao->tipo) {
            'reabertura_edicao' => 'Reabertura de edição de pauta',
            'extensao_prazo'    => 'Extensão de prazo de lançamento',
            default             => $solicitacao->tipo,
        };

        return (new MailMessage)
            ->subject('Novo pedido de edição de pauta pendente')
            ->view('mail.pauta.solicitacao-edicao-director', [
                'nomeDirector'   => $this->director->nome,
                'nomeProfessor'  => $solicitacao->professor->nome ?? '—',
                'tipo'           => $solicitacao->tipo,
                'tipoLabel'      => $tipoLabel,
                'disciplina'     => $solicitacao->turmaDisciplinaProfessor
                                         ->classeTurnoDisciplina->disciplina->nome ?? '—',
                'turma'          => $solicitacao->turmaDisciplinaProfessor->turma->nome ?? '—',
                'periodo'        => $solicitacao->periodo,
                'motivo'         => $solicitacao->motivo,
                'urlSolicitacoes' => route('tenant.dashboard.pautas.solicitacoes.index'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $nomeProfessor = $this->solicitacao->professor->nome ?? '—';

        $tipoLabel = match ($this->solicitacao->tipo) {
            'reabertura_edicao' => 'reabertura de edição de pauta',
            'extensao_prazo'    => 'extensão de prazo de lançamento',
            default             => $this->solicitacao->tipo,
        };

        return [
            'tipo'     => 'solicitacao_edicao_pauta_pendente',
            'titulo'   => 'Novo pedido de edição de pauta',
            'mensagem' => "O professor {$nomeProfessor} solicitou {$tipoLabel}. O pedido aguarda a sua decisão.",
        ];
    }
}