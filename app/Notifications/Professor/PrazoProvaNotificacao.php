<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\PrazoProva;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoProvaNotificacao extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public const TIPO_CRIADO     = 'criado';
    public const TIPO_PRORROGADO = 'prorrogado';
    public const TIPO_FECHADO    = 'fechado';
    public const TIPO_A_EXPIRAR  = 'a_expirar';
    public const TIPO_EXPIRADO   = 'expirado';

    public function __construct(
        public PrazoProva $prazo,
        public string $tipo
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->assunto())
            ->view('mail.professor.prazo-prova', [
                'nome'         => $notifiable->nome,
                'tipo'         => $this->tipo,
                'titulo'       => $this->tituloPrazo(),
                'disciplina'   => $this->prazo->disciplina?->nome,
                'classe'       => $this->prazo->classe?->nome,
                'dataLimite'   => $this->prazo->data_limite?->format('d/m/Y H:i') ?? '—',
                'periodo'      => $this->prazo->periodo,
                'url'          => url('/dashboard/professor/provas'),
                'instituicao'  => $notifiable->instituicao,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => $this->tipo,
            'titulo'   => $this->titulo(),
            'mensagem' => $this->mensagem(),
            'url'      => '/dashboard/professor/provas',
        ];
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function assunto(): string
    {
        return match ($this->tipo) {
            self::TIPO_CRIADO     => 'Novo prazo de prova criado',
            self::TIPO_PRORROGADO => 'Prazo de prova prorrogado',
            self::TIPO_FECHADO    => 'Prazo de prova encerrado',
            self::TIPO_A_EXPIRAR  => 'Prazo a expirar em breve',
            self::TIPO_EXPIRADO   => 'Prazo de prova expirado',
            default               => 'Atualização de prazo',
        };
    }

    private function titulo(): string
    {
        return match ($this->tipo) {
            self::TIPO_CRIADO     => 'Novo prazo de prova',
            self::TIPO_PRORROGADO => 'Prazo prorrogado',
            self::TIPO_FECHADO    => 'Prazo encerrado',
            self::TIPO_A_EXPIRAR  => 'Prazo a expirar',
            self::TIPO_EXPIRADO   => 'Prazo expirado',
            default               => 'Atualização de prazo',
        };
    }

    private function mensagem(): string
    {
        return match ($this->tipo) {
            self::TIPO_CRIADO     => "Foi criado um novo prazo para \"{$this->tituloPrazo()}\".",
            self::TIPO_PRORROGADO => "O prazo \"{$this->tituloPrazo()}\" foi prorrogado.",
            self::TIPO_FECHADO    => "O prazo \"{$this->tituloPrazo()}\" foi encerrado pelo diretor.",
            self::TIPO_A_EXPIRAR  => "Falta menos de 30 minutos para o prazo \"{$this->tituloPrazo()}\" terminar.",
            self::TIPO_EXPIRADO   => "O prazo \"{$this->tituloPrazo()}\" expirou.",
            default               => 'O prazo foi atualizado.',
        };
    }

    private function tituloPrazo(): string
    {
        return $this->prazo->titulo ?? $this->prazo->tipo_prova ?? 'Prazo';
    }
}