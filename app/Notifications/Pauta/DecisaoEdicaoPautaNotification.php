<?php

namespace App\Notifications\Pauta;

use App\Models\Tenant\SolicitacaoEdicaoPauta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DecisaoEdicaoPautaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SolicitacaoEdicaoPauta $solicitacao
    ) {}

    public function via(object $notifiable): array
    {
        $canais = ['database'];

        if (!empty($notifiable->email)) {
            $canais[] = 'mail';
        }

        return $canais;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tipoLabel = match ($this->solicitacao->tipo) {
            'reabertura_edicao' => 'Reabertura de edição de pauta',
            'extensao_prazo'    => 'Extensão de prazo de lançamento',
            default             => $this->solicitacao->tipo,
        };

        $aprovada = $this->solicitacao->status === 'aprovada';

        return (new MailMessage)
            ->subject($aprovada ? 'Pedido de edição de pauta aprovado' : 'Pedido de edição de pauta rejeitado')
            ->view('mail.pauta.decisao-edicao-professor', [
                'nomeProfessor'   => $this->solicitacao->professor->nome,
                'tipo'            => $this->solicitacao->tipo,
                'tipoLabel'       => $tipoLabel,
                'disciplina'      => $this->solicitacao->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->nome ?? '—',
                'turma'           => $this->solicitacao->turmaDisciplinaProfessor->turma->nome ?? '—',
                'periodo'         => $this->solicitacao->periodo,
                'aprovada'        => $aprovada,
                'observacao'      => $this->solicitacao->observacao,
                'prazoEdicaoAte'  => $this->solicitacao->prazo_edicao_ate?->format('d/m/Y H:i'),
                'urlPautas'       => route('tenant.dashboard.pautas.cursos'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $aprovada = $this->solicitacao->status === 'aprovada';

        $tipoLabel = match ($this->solicitacao->tipo) {
            'reabertura_edicao' => 'reabertura de edição de pauta',
            'extensao_prazo'    => 'extensão de prazo de lançamento',
            default             => $this->solicitacao->tipo,
        };

        return [
            'tipo'     => 'decisao_edicao_pauta',
            'titulo'   => $aprovada ? 'Pedido aprovado' : 'Pedido rejeitado',
            'mensagem' => $aprovada
                ? "O seu pedido de {$tipoLabel} foi aprovado. Já pode aceder à pauta."
                : "O seu pedido de {$tipoLabel} foi rejeitado." . ($this->solicitacao->observacao ? ' Motivo: ' . $this->solicitacao->observacao : ''),
        ];
    }
}