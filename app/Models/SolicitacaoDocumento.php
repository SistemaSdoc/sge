<?php

namespace App\Models;

use App\Notifications\PagamentoConfirmadoNotification;
use App\Notifications\RupeDisponivelNotification;
use App\Notifications\SolicitacaoDocumentoStatusNotification;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SolicitacaoDocumento extends Model
{
    use HasUuid;

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_APROVADO = 'aprovado';

    public const STATUS_REJEITADO = 'rejeitado';

    public const STATUS_PAGO = 'pago';

    public const STATUS_PRONTO = 'pronto';

    public const STATUS_ENTREGUE = 'entregue';

    protected $table = 'solicitacoes_documentos';

    protected $fillable = [
        'aluno_id',
        'curso_id',
        'turma_id',
        'classe_id',
        'ano_lectivo_id',
        'instituicao_origem_id',
        'instituicao_tutora_id',
        'instituicao_aprovadora_id',
        'instituicao_emissora_id',
        'tipo_documento',
        'motivo',
        'observacoes',
        'status',
        'numero_processo',
        'numero_registro_tutora',
        'data_solicitacao',
        'data_aprovacao',
        'data_emissao',
        'data_pronto',
        'data_pagamento_confirmado',
        // Rupe / pagamento
        'rupe_referencia',
        'rupe_entidade',
        'rupe_valor',
        'rupe_gerado_em',
        'estado_pagamento',
        'data_levantamento',
        'levantado_por_id',
    ];

    protected $casts = [
        'data_solicitacao' => 'datetime',
        'data_aprovacao' => 'datetime',
        'data_emissao' => 'datetime',
        'data_pronto' => 'datetime',
        'data_pagamento_confirmado' => 'datetime',
        'rupe_gerado_em' => 'datetime',
        'rupe_valor' => 'decimal:2',
        'data_levantamento' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $solicitacao): void {
            if (blank($solicitacao->instituicao_emissora_id)) {
                $solicitacao->instituicao_emissora_id = $solicitacao->instituicao_origem_id
                    ?? $solicitacao->instituicao_tutora_id;
            }

            if (blank($solicitacao->instituicao_tutora_id)) {
                $solicitacao->instituicao_tutora_id = $solicitacao->instituicao_origem_id
                    ?? $solicitacao->instituicao_emissora_id;
            }

            if (blank($solicitacao->instituicao_aprovadora_id)) {
                $solicitacao->instituicao_aprovadora_id = $solicitacao->instituicao_tutora_id
                    ?? $solicitacao->instituicao_emissora_id;
            }

            if (blank($solicitacao->numero_processo)) {
                $solicitacao->numero_processo = self::gerarNumeroProcesso(
                    $solicitacao->instituicao_emissora_id,
                    $solicitacao->data_solicitacao ?? now()
                );
            }
        });
    }

    public static function gerarNumeroProcesso(?string $instituicaoId, $dataReferencia = null): string
    {
        $instituicaoId ??= 'geral';
        $dataReferencia ??= now();

        $ultimoNumero = self::query()
            ->where('instituicao_emissora_id', $instituicaoId)
            ->whereNotNull('numero_processo')
            ->orderByRaw("CAST(SUBSTRING_INDEX(numero_processo, '/', 1) AS UNSIGNED) DESC")
            ->value('numero_processo');

        $sequencia = 1;

        if ($ultimoNumero) {
            $parteSequencial = (int) explode('/', $ultimoNumero)[0];
            $sequencia = $parteSequencial + 1;
        }

        return sprintf('%05d/%s', $sequencia, $dataReferencia->year ?? now()->year);
    }

    /**
     * Retorna o ID da instituição responsável pela solicitação (para decisões, pagamento e levantamento).
     */
   public function instituicaoResponsavelId(): ?string
{

    if ($this->tipo_documento === 'certificado') {
        return $this->instituicao_tutora_id;
    }


    if ($this->instituicao_emissora_id) {
        return $this->instituicao_emissora_id;
    }

    // Fallback: origem ou tutora (se emissora não estiver definida)
    return $this->instituicao_origem_id ?? $this->instituicao_tutora_id;
}

    public function getResponsavelInstituicaoIdAttribute(): ?int
    {
        return $this->instituicaoResponsavelId();
    }

    public function getFluxoStatusAttribute(): string
    {
        if ($this->status === self::STATUS_ENTREGUE || $this->data_levantamento) {
            return self::STATUS_ENTREGUE;
        }

        if ($this->status === self::STATUS_PRONTO || $this->data_emissao) {
            return self::STATUS_PRONTO;
        }

        if ($this->status === self::STATUS_PAGO || $this->estado_pagamento === 'pago') {
            return self::STATUS_PAGO;
        }

        if ($this->status === self::STATUS_APROVADO) {
            return self::STATUS_APROVADO;
        }

        if ($this->status === self::STATUS_REJEITADO) {
            return self::STATUS_REJEITADO;
        }

        return self::STATUS_PENDENTE;
    }

    public function aprovar(?string $instituicaoAprovadoraId = null, ?string $observacoes = null): void
    {
        $this->status = self::STATUS_APROVADO;
        $this->instituicao_aprovadora_id = $instituicaoAprovadoraId ?? $this->instituicao_aprovadora_id;
        $this->observacoes = $observacoes ?? $this->observacoes;
        $this->data_aprovacao = now();
        $this->save();

        $this->notificarStatus(
            'Pedido aprovado',
            'A sua solicitação de documento foi aprovada.',
            'aluno',
            route('solicitacoes-documentos.index')
        );

        $this->notificarStatus(
            'Pedido aprovado',
            'A solicitação de '.$this->tipoLabel.' foi aprovada pela tutela.',
            'instituicao',
            route('solicitacoes-documentos.colegio.index')
        );

        $this->notificarStatus(
            'Pedido aprovado',
            'A solicitação de '.$this->tipoLabel.' foi aprovada pela tutela.',
            'tutela',
            route('solicitacoes-documentos.tutela.index')
        );
    }

    public function rejeitar(): void
    {
        $this->status = self::STATUS_REJEITADO;
        $this->save();

        $this->notificarStatus('Pedido rejeitado', 'Pedido rejeitado pela tutela.');
    }

    public function emitir(?string $numeroRegistroTutela = null): void
    {
        $this->status = self::STATUS_PRONTO;
        $this->numero_registro_tutora = $numeroRegistroTutela ?? $this->numero_registro_tutora;
        $this->data_emissao = $this->data_emissao ?? now();
        $this->data_pronto = $this->data_pronto ?? $this->data_emissao;
        $this->save();

        $this->notificarStatus(
            'Documento pronto',
            'O teu documento está pronto, podes dirigir-te à secretaria para o levantar.',
            'aluno',
            route('solicitacoes-documentos.index')
        );

        $this->notificarStatus(
            'Documento pronto',
            'O documento de '.$this->tipoLabel.' foi marcado como pronto para levantamento.',
            'instituicao',
            route('solicitacoes-documentos.colegio.index')
        );

        $this->notificarStatus(
            'Documento pronto',
            'O documento de '.$this->tipoLabel.' foi marcado como pronto para levantamento.',
            'tutela',
            route('solicitacoes-documentos.tutela.index')
        );
    }

    protected function notificarAUsuariosDaInstituicao(string $instituicaoId, string $titulo, string $mensagem, ?string $rota = null): void
    {
        if (! $instituicaoId) {
            return;
        }

        $users = User::query()
            ->where('instituicao_id', $instituicaoId)
            ->get()
            ->reject(fn (User $user) => $user->hasRole('Aluno') || $user->hasRole('Candidato'));

        foreach ($users as $user) {
            $user->notify(new SolicitacaoDocumentoStatusNotification(
                titulo: $titulo,
                mensagem: $mensagem,
                solicitacaoId: $this->id,
                tipoDocumento: $this->tipoLabel,
                categoria: 'instituicao',
                url: $rota ?? route('solicitacoes-documentos.colegio.index'),
            ));
        }
    }

    public function notificarStatus(string $titulo, string $mensagem, ?string $categoria = 'aluno', ?string $rota = null): void
    {
        if ($categoria === 'instituicao') {
            $this->notificarAUsuariosDaInstituicao($this->instituicao_origem_id, $titulo, $mensagem, $rota ?? route('solicitacoes-documentos.colegio.index'));

            return;
        }

        if ($categoria === 'tutela') {
            $this->notificarAUsuariosDaInstituicao($this->instituicao_tutora_id, $titulo, $mensagem, $rota ?? route('solicitacoes-documentos.tutela.index'));

            return;
        }

        $user = $this->aluno?->user;

        if (! $user) {
            return;
        }

        $user->notify(new SolicitacaoDocumentoStatusNotification(
            titulo: $titulo,
            mensagem: $mensagem,
            solicitacaoId: $this->id,
            tipoDocumento: $this->tipoLabel,
            categoria: 'aluno',
            url: $rota ?? route('solicitacoes-documentos.index'),
        ));
    }

    /**
     * Marca os dados do rupe gerados e notifica o aluno.
     * Espera um array com chaves: referencia, entidade, valor, gerado_em (opcional)
     */
    public function marcarRupeGerado(array $dados, bool $notify = true): void
    {
        $this->rupe_referencia = $dados['referencia'] ?? null;
        $this->rupe_entidade = $dados['entidade'] ?? null;
        $this->rupe_valor = $dados['valor'] ?? $this->rupe_valor;
        $this->rupe_gerado_em = $dados['gerado_em'] ?? now();
        $this->save();

        if ($notify) {
            $user = $this->aluno?->user;

            if ($user) {
                $user->notify(new RupeDisponivelNotification(
                    $this,
                    categoria: 'aluno',
                    url: route('solicitacoes-documentos.index'),
                ));
            }
        }
    }

    /**
     * Marca a solicitação como paga (utilizado pela secretaria) e notifica o aluno.
     */
    public function marcarComoPago(): void
    {
        $this->status = self::STATUS_PAGO;
        $this->estado_pagamento = 'pago';
        $this->data_pagamento_confirmado = $this->data_pagamento_confirmado ?? now();
        $this->save();

        $user = $this->aluno?->user;

        if ($user) {
            $user->notify(new PagamentoConfirmadoNotification(
                $this,
                categoria: 'aluno',
                url: route('solicitacoes-documentos.index'),
            ));
        }

        $this->notificarStatus(
            'Pagamento confirmado',
            'O pagamento foi confirmado para a solicitação de '.$this->tipoLabel.'.',
            'instituicao',
            route('solicitacoes-documentos.colegio.index')
        );

        $this->notificarStatus(
            'Pagamento confirmado',
            'O pagamento foi confirmado para a solicitação de '.$this->tipoLabel.'.',
            'tutela',
            route('solicitacoes-documentos.tutela.index')
        );
    }

    /**
     * Registra o levantamento físico do documento pelo aluno.
     * Opcionalmente pode receber o utilizador que registou o levantamento.
     */
    public function marcarComoLevantado(?User $usuario = null): void
    {
        $this->status = self::STATUS_ENTREGUE;
        $this->data_levantamento = now();
        if ($usuario) {
            $this->levantado_por_id = $usuario->id;
        }
        $this->save();
    }

    public function getTipoLabelAttribute(): string
    {
        // Preferir labels definidos em config/documentos.php para manter
        // um único local de verdade. Se a configuração não estiver presente,
        // usar rótulos por defeito.
        $tiposBase = config('documentos.types_base', []);

        foreach ($tiposBase as $tipo) {
            if (($tipo['value'] ?? null) === $this->tipo_documento) {
                return $tipo['label'] ?? (string) $this->tipo_documento;
            }
        }

        $cert = config('documentos.certificado');
        if (isset($cert['value']) && $cert['value'] === $this->tipo_documento) {
            return $cert['label'] ?? (string) $this->tipo_documento;
        }

        // Fallback antigo
        return match ($this->tipo_documento) {
            'declaracao' => 'Declaração',
            'historico' => 'Histórico',
            'declaracao_com_notas' => 'Declaração com notas',
            default => ucfirst((string) $this->tipo_documento),
        };
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anoLectivo()
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function instituicaoOrigem()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_origem_id');
    }

    public function instituicaoTutora()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_tutora_id');
    }

    public function instituicaoAprovadora()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_aprovadora_id');
    }

    public function instituicaoEmissora()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_emissora_id');
    }

    public function levantadoPor()
    {
        return $this->belongsTo(User::class, 'levantado_por_id');
    }
}