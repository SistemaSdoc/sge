<?php

namespace App\Notifications\Professor;

use App\Models\Tenant\PrazoProva;
use App\Notifications\Concerns\ReliableNotification;
use App\Notifications\Concerns\TenantNotificationUrl;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrazoProvaNotificacao extends Notification
{
    use ReliableNotification;
    use TenantNotificationUrl;

    public const TIPO_CRIADO = 'criado';

    public const TIPO_PRORROGADO = 'prorrogado';

    public const TIPO_FECHADO = 'fechado';

    public const TIPO_A_EXPIRAR = 'a_expirar';

    public const TIPO_EXPIRADO = 'expirado';

    /**
     * Prazo individual. Pode ser null quando a notificação é consolidada
     * (vários prazos agrupados por professor).
     */
    public ?PrazoProva $prazo;

    public string $tipo;

    /** @var array<int, array<string, mixed>> */
    public array $prazosConsolidados;

    /**
     * @param  array<int, array<string, mixed>>  $prazosConsolidados
     */
    public function __construct(
        ?PrazoProva $prazo = null,
        string $tipo = self::TIPO_CRIADO,
        array $prazosConsolidados = [],
    ) {
        $this->prazo = $prazo;
        $this->tipo = $tipo;
        $this->prazosConsolidados = $prazosConsolidados;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    // ─────────────────────────────────────────────────────
    // BANCO DE DADOS (sininho)
    // ─────────────────────────────────────────────────────
    public function toArray(object $notifiable): array
    {
        $instituicao = $this->nomeInstituicao($notifiable);

        return $this->comporNotificacao($instituicao);
    }

    /**
     * @return array{
     *     titulo: string,
     *     intro: string,
     *     linhas: string[],
     *     diretrizes: string|null,
     *     fecho: string|null,
     *     assinatura: string,
     *     tipo: string,
     *     url: string,
     *     prazos: array
     * }
     */
    private function comporNotificacao(string $instituicao): array
    {
        // ── Modo consolidado ──
        if (! empty($this->prazosConsolidados)) {
            $total = count($this->prazosConsolidados);

            $diretrizes = collect($this->prazosConsolidados)
                ->pluck('descricao')
                ->filter()
                ->first();

            $linhas = collect($this->prazosConsolidados)
                ->map(fn ($p) => sprintf(
                    '%s — turma(s): %s — limite: %s',
                    $p['disciplina'] ?? '—',
                    implode(', ', $p['turmas'] ?? []),
                    $p['data_limite'] ?? '—',
                ))
                ->values()
                ->all();

            return [
                'titulo' => "Novo(s) prazo(s) de submissão ({$total})",
                'intro' => 'Cumpre-nos informar que, por deliberação da Direção, foram abertos os seguintes prazos de submissão de provas:',
                'linhas' => $linhas,
                'diretrizes' => $diretrizes ?: null,
                'fecho' => 'Solicita-se o cumprimento rigoroso do prazo indicado. A submissão deve ser efetuada através da plataforma institucional.',
                'assinatura' => "Com os melhores cumprimentos,\nA Direção — {$instituicao}",
                'tipo' => $this->tipo,
                'url' => $this->tenantNotificationUrl('tenant.dashboard.professor.provas.index'),
                'prazos' => $this->prazosConsolidados,
            ];
        }

        // ── Modo individual ──
        $titulo = $this->prazo?->titulo_apresentacao ?? 'Prazo de provas';
        $disciplina = $this->prazo?->disciplina?->nome ?? 'Todas as disciplinas';
        $limite = $this->prazo?->data_limite?->format('d/m/Y H:i') ?? '—';
        $diretrizes = $this->prazo?->observacoes;

        [$tituloNotif, $intro, $fecho] = match ($this->tipo) {
            self::TIPO_CRIADO => [
                'Novo prazo de submissão',
                'Cumpre-nos informar que foi aberto um novo prazo de submissão de provas.',
                'Solicita-se o cumprimento do prazo através da plataforma institucional.',
            ],
            self::TIPO_PRORROGADO => [
                'Prorrogação de prazo',
                'Cumpre-nos informar que o prazo de submissão foi prorrogado.',
                'Solicita-se o cumprimento do novo prazo através da plataforma institucional.',
            ],
            self::TIPO_FECHADO => [
                'Encerramento de prazo',
                'Cumpre-nos informar que o prazo de submissão foi encerrado.',
                null,
            ],
            self::TIPO_A_EXPIRAR => [
                'Prazo a expirar',
                'Cumpre-nos informar que o prazo de submissão se encontra prestes a expirar.',
                'Solicita-se a submissão com a maior brevidade possível.',
            ],
            self::TIPO_EXPIRADO => [
                'Prazo expirado',
                'Cumpre-nos informar que o prazo de submissão expirou.',
                null,
            ],
            default => [
                'Comunicação da Direção',
                'Cumpre-nos informar o seguinte:',
                null,
            ],
        };

        return [
            'titulo' => $tituloNotif,
            'intro' => $intro,
            'linhas' => [
                "Referência: {$titulo}",
                "Disciplina: {$disciplina}",
                "Data limite: {$limite}",
            ],
            'diretrizes' => $diretrizes ?: null,
            'fecho' => $fecho,
            'assinatura' => "Com os melhores cumprimentos,\nA Direção — {$instituicao}",
            'tipo' => $this->tipo,
            'url' => $this->tenantNotificationUrl('tenant.dashboard.professor.provas.index'),
            'prazos' => $this->prazo ? [['prazo_id' => $this->prazo->id]] : [],
        ];
    }

    // ─────────────────────────────────────────────────────
    // EMAIL (opcional)
    // ─────────────────────────────────────────────────────
    public function toMail(object $notifiable): MailMessage
    {
        $instituicao = $notifiable->instituicao;
        $url = $this->tenantNotificationUrl('tenant.dashboard.professor.provas.index');

        $titulo = $this->prazo
            ? ($this->prazo->titulo_apresentacao ?? 'Prazo de prova')
            : 'Novos prazos de prova';

        $disciplina = $this->prazo?->disciplina?->nome;
        $classe = $this->prazo?->classe?->nome;
        $dataLimite = $this->prazo?->data_limite?->format('d/m/Y H:i') ?? '—';
        $periodo = $this->prazo?->periodo;

        $assunto = match ($this->tipo) {
            self::TIPO_CRIADO => 'Novo prazo de submissão de prova',
            self::TIPO_PRORROGADO => 'Prazo de prova prorrogado',
            self::TIPO_FECHADO => 'Prazo de prova encerrado',
            self::TIPO_A_EXPIRAR => 'Prazo de prova a expirar',
            self::TIPO_EXPIRADO => 'Prazo de prova expirado',
            default => 'Atualização de prazo de prova',
        };

        return (new MailMessage)
            ->subject($assunto)
            ->view('mail.professor.prazo-prova', [
                'nome' => $notifiable->nome,
                'instituicao' => $instituicao,
                'tipo' => $this->tipo,
                'titulo' => $titulo,
                'disciplina' => $disciplina,
                'classe' => $classe,
                'dataLimite' => $dataLimite,
                'periodo' => $periodo,
                'prazosConsolidados' => $this->prazosConsolidados,
                'url' => $url,
            ]);
    }

    // ─────────────────────────────────────────────────────
    // COMPOSIÇÃO FORMAL — cabeçalho + corpo + rodapé
    // ─────────────────────────────────────────────────────

    private function nomeInstituicao(object $notifiable): string
    {
        return $notifiable->instituicao?->nome
            ?? config('app.name', 'Instituição de Ensino');
    }

    /**
     * @return array{0: string, 1: string} [assunto, corpo]
     */
    private function comporConteudo(string $instituicao): array
    {
        // ── Modo consolidado ──
        if (! empty($this->prazosConsolidados)) {
            $total = count($this->prazosConsolidados);

            $assunto = "Comunicação da Direção — {$total} novo(s) prazo(s) de submissão de provas";

            $diretrizes = collect($this->prazosConsolidados)
                ->pluck('descricao')
                ->filter()
                ->first();

            $linhas = collect($this->prazosConsolidados)
                ->map(fn ($p) => sprintf(
                    '  • %s — turma(s): %s — limite: %s',
                    $p['disciplina'] ?? '—',
                    implode(', ', $p['turmas'] ?? []),
                    $p['data_limite'] ?? '—',
                ))
                ->implode("\n");

            $corpo = 'Cumpre-nos informar que, por deliberação da Direção, '
                ."foram abertos os seguintes prazos de submissão de provas:\n\n"
                ."{$linhas}\n\n";

            if ($diretrizes) {
                $corpo .= "Diretrizes da Direção:\n{$diretrizes}\n\n";
            }

            $corpo .= 'Solicita-se o cumprimento rigoroso do prazo indicado. '
                ."A submissão deve ser efetuada através da plataforma institucional.\n\n"
                ."Com os melhores cumprimentos,\n"
                ."A Direção — {$instituicao}";

            return [$assunto, $corpo];
        }

        // ── Modo individual ──
        $titulo = $this->prazo?->titulo ?? $this->prazo?->tipo_prova ?? 'Prazo de provas';
        $disciplina = $this->prazo?->disciplina?->nome ?? 'Todas as disciplinas';
        $limite = $this->prazo?->data_limite?->format('d/m/Y H:i') ?? '—';
        $diretrizes = $this->prazo?->observacoes;

        $assunto = match ($this->tipo) {
            self::TIPO_CRIADO => 'Comunicação da Direção — Novo prazo de submissão',
            self::TIPO_PRORROGADO => 'Comunicação da Direção — Prorrogação de prazo',
            self::TIPO_FECHADO => 'Comunicação da Direção — Encerramento de prazo',
            self::TIPO_A_EXPIRAR => 'Comunicação da Direção — Prazo a expirar',
            self::TIPO_EXPIRADO => 'Comunicação da Direção — Prazo expirado',
            default => 'Comunicação da Direção',
        };

        $abertura = match ($this->tipo) {
            self::TIPO_CRIADO => 'Cumpre-nos informar que foi aberto um novo prazo de submissão de provas.',
            self::TIPO_PRORROGADO => 'Cumpre-nos informar que o prazo de submissão foi prorrogado.',
            self::TIPO_FECHADO => 'Cumpre-nos informar que o prazo de submissão foi encerrado.',
            self::TIPO_A_EXPIRAR => 'Cumpre-nos informar que o prazo de submissão se encontra prestes a expirar.',
            self::TIPO_EXPIRADO => 'Cumpre-nos informar que o prazo de submissão expirou.',
            default => 'Cumpre-nos informar o seguinte:',
        };

        $corpo = "{$abertura}\n\n"
            ."  Referência: {$titulo}\n"
            ."  Disciplina: {$disciplina}\n"
            ."  Data limite: {$limite}\n\n";

        if ($diretrizes) {
            $corpo .= "Diretrizes da Direção:\n{$diretrizes}\n\n";
        }

        if (in_array($this->tipo, [self::TIPO_CRIADO, self::TIPO_PRORROGADO, self::TIPO_A_EXPIRAR], true)) {
            $corpo .= "Solicita-se o cumprimento do prazo através da plataforma institucional.\n\n";
        }

        $corpo .= "Com os melhores cumprimentos,\n"
            ."A Direção — {$instituicao}";

        return [$assunto, $corpo];
    }
}
