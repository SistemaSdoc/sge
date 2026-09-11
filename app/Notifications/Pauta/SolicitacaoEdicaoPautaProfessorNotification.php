<?php

namespace App\Notifications\Pauta;

use App\Models\Tenant\SolicitacaoEdicaoPauta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitacaoEdicaoPautaProfessorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SolicitacaoEdicaoPauta $solicitacao
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $solicitacao = $this->solicitacao->load([
            'turmaDisciplinaProfessor.turma',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        $tipoLabel = match ($solicitacao->tipo) {
            'reabertura_edicao' => 'Reabertura de edição de pauta',
            'extensao_prazo'    => 'Extensão de prazo de lançamento',
            default             => $solicitacao->tipo,
        };

        return (new MailMessage)
            ->subject('Pedido de edição de pauta submetido')
            ->view('mail.pauta.solicitacao-edicao-professor', [
                'nomeProfessor' => $solicitacao->professor->nome,
                'tipo'          => $solicitacao->tipo,
                'tipoLabel'     => $tipoLabel,
                'disciplina'    => $solicitacao->turmaDisciplinaProfessor
                                        ->classeTurnoDisciplina->disciplina->nome ?? '—',
                'turma'         => $solicitacao->turmaDisciplinaProfessor->turma->nome ?? '—',
                'periodo'       => $solicitacao->periodo,
                'motivo'        => $solicitacao->motivo,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $tipoLabel = match ($this->solicitacao->tipo) {
            'reabertura_edicao' => 'reabertura de edição de pauta',
            'extensao_prazo'    => 'extensão de prazo de lançamento',
            default             => $this->solicitacao->tipo,
        };

        return [
            'tipo'     => 'solicitacao_edicao_pauta_submetida',
            'titulo'   => 'Pedido submetido com sucesso',
            'mensagem' => "O seu pedido de {$tipoLabel} foi enviado ao director e está pendente de aprovação.",
        ];
    }
}