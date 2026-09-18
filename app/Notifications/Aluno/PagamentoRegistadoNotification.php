<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\Pagamento;
use App\Notifications\Concerns\ReliableNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PagamentoRegistadoNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public function __construct(
        public Pagamento $pagamento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $instituicao = $notifiable->instituicao;

        $mail = (new MailMessage)
            ->subject('Pagamento registado com sucesso')
            ->view('mail.aluno.pagamento-registado', [
                'nome' => $notifiable->nome,
                'valorTotal' => $this->valorFormatado(),
                'dataPagamento' => $this->dataFormatada(),
                'metodo' => $this->pagamento->metodo,
                'referencia' => $this->pagamento->referencia,
                'numeroRecibo' => $this->pagamento->numero_recibo,
                'instituicao' => $instituicao,
                'artigoInstituicao' => match ($instituicao->tipo) {
                    'instituto' => 'ao',
                    'colegio' => 'ao',
                    default => 'à',
                },
            ]);

        $disco = Storage::disk('private');
        if ($this->pagamento->recibo_path && $disco->exists($this->pagamento->recibo_path)) {
            $mail->attachData(
                $disco->get($this->pagamento->recibo_path),
                "recibo-{$this->pagamento->numero_recibo}.pdf",
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'pagamento_registado',
            'titulo' => 'Pagamento registado',
            'mensagem' => 'O seu pagamento de '.$this->valorFormatado().' AOA foi registado com sucesso.',
            'pagamento_id' => $this->pagamento->id,
            'valor_total' => $this->pagamento->valor_total,
            'data_pagamento' => $this->dataFormatada(),
            'numero_recibo' => $this->pagamento->numero_recibo,
        ];
    }

    private function valorFormatado(): string
    {
        return number_format((float) ($this->pagamento->valor_total ?? 0), 2, ',', '.');
    }

    private function dataFormatada(): ?string
    {
        $dataPagamento = $this->pagamento->data_pagamento;

        return $dataPagamento
            ? Carbon::parse((string) $dataPagamento)->format('d/m/Y')
            : null;
    }
}
