<?php

namespace App\Notifications;

use App\Models\PrazoProva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoProvaNotificacao extends Notification
{
    use Queueable;

    /**
     * Tipos de notificação suportados.
     */
    public const TIPO_CRIADO     = 'criado';
    public const TIPO_PRORROGADO = 'prorrogado';
    public const TIPO_FECHADO    = 'fechado';
    public const TIPO_A_EXPIRAR  = 'a_expirar';
    public const TIPO_EXPIRADO   = 'expirado';

    public function __construct(
        public PrazoProva $prazo,
        public string $tipo
    ) {}

    /**
     * Canais de entrega.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Conteúdo do email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $assunto = $this->assunto();
        $mensagem = $this->mensagem();
        $icone = $this->icone();

        return (new MailMessage)
            ->subject("{$icone} {$assunto}")
            ->greeting("Olá, {$notifiable->nome}")
            ->line($mensagem)
            ->line('**Detalhes do prazo:**')
            ->line("• Título: {$this->tituloPrazo()}")
            ->line("• Disciplina: {$this->prazo->disciplina?->nome }")
            ->line("• Classe: {$this->prazo->classe?->nome }")
            ->line("• Data Limite: {$this->prazo->data_limite->format('d/m/Y H:i')}")
            ->line("• Período: {$this->prazo->periodo}")
            ->action('Ver Prazos', url('/dashboard/professor/provas'))
            ->line('Obrigado!');
    }

    /**
     * Dados guardados na tabela `notifications`.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo'        => $this->tipo,
            'prazo_id'    => $this->prazo->id,
            'titulo'      => $this->tituloPrazo(),
            'disciplina'  => $this->prazo->disciplina?->nome,
            'classe'      => $this->prazo->classe?->nome,
            'data_limite' => $this->prazo->data_limite->format('d/m/Y H:i'),
            'periodo'     => $this->prazo->periodo,
            'url'         => '/dashboard/professor/provas',
        ];
    }

    // ============================================================
    // MÉTODOS PRIVADOS AUXILIARES
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

    private function mensagem(): string
    {
        return match ($this->tipo) {
            self::TIPO_CRIADO     => 'Foi criado um novo prazo para submissão de provas. Consulte os detalhes abaixo e submeta a sua prova dentro do prazo.',
            self::TIPO_PRORROGADO => 'O prazo foi prorrogado. Tem mais tempo para submeter a sua prova.',
            self::TIPO_FECHADO    => 'O prazo foi encerrado manualmente pelo diretor. Já não é possível submeter provas para este prazo.',
            self::TIPO_A_EXPIRAR  => '⚠️ Falta menos de 30 minutos para o prazo terminar! Se ainda não submeteu a sua prova, faça-o agora.',
            self::TIPO_EXPIRADO   => 'O prazo expirou e já não é possível submeter a prova. Se não conseguiu submeter, contacte o diretor.',
            default               => 'O prazo foi atualizado.',
        };
    }

    private function icone(): string
    {
        return match ($this->tipo) {
            self::TIPO_CRIADO     => '📝',
            self::TIPO_PRORROGADO => '⏰',
            self::TIPO_FECHADO    => '🔒',
            self::TIPO_A_EXPIRAR  => '⚠️',
            self::TIPO_EXPIRADO   => '❌',
            default               => '📢',
        };
    }

    private function tituloPrazo(): string
    {
        return $this->prazo->titulo ?? $this->prazo->tipo_prova;
    }
}