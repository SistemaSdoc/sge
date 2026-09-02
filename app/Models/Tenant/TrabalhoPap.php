<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'grupo_pap_id',
    'status',
    'aprovado_por_id',
    'data_aprovacao',
])]
class TrabalhoPap extends Model
{
    use HasUuid;

    protected $table = 'trabalho_pap';

    const STATUS_PENDENTE_ENTREGA = 'pendente_entrega';

    const STATUS_EM_ANALISE_TUTOR = 'em_analise_tutor';

    const STATUS_CORRECAO_TUTOR = 'correcao_tutor';

    const STATUS_EM_ANALISE_COORDENACAO = 'em_analise_coordenacao';

    const STATUS_CORRECAO_COORDENACAO = 'correcao_coordenacao';

    const STATUS_APROVADO = 'aprovado';

    protected function casts(): array
    {
        return [
            'data_aprovacao' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ── Relações ────────────────────────────────────────────

    public function grupoPap()
    {
        return $this->belongsTo(GrupoPap::class, 'grupo_pap_id');
    }

    public function versoes()
    {
        return $this->hasMany(TrabalhoPapVersao::class, 'trabalho_pap_id')
            ->orderBy('numero_versao');
    }

    public function versaoAtual()
    {
        return $this->hasOne(TrabalhoPapVersao::class, 'trabalho_pap_id')
            ->latestOfMany('numero_versao');
    }

    public function feedbacks()
    {
        return $this->hasMany(TrabalhoPapFeedback::class, 'trabalho_pap_id')
            ->latest();
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopePendenteEntrega($query)
    {
        return $query->where('status', self::STATUS_PENDENTE_ENTREGA);
    }

    public function scopeEmAnaliseTutor($query)
    {
        return $query->where('status', self::STATUS_EM_ANALISE_TUTOR);
    }

    public function scopeEmAnaliseCoordenacao($query)
    {
        return $query->where('status', self::STATUS_EM_ANALISE_COORDENACAO);
    }

    public function scopeAprovados($query)
    {
        return $query->where('status', self::STATUS_APROVADO);
    }

    // ── Regras de negócio ───────────────────────────────────

    public function podeSerSubmetido(): bool
    {
        // Aluno pode submeter quando está à espera de entrega
        // ou quando foi pedida correção (tutor ou coordenação)
        return in_array($this->status, [
            self::STATUS_PENDENTE_ENTREGA,
            self::STATUS_CORRECAO_TUTOR,
            self::STATUS_CORRECAO_COORDENACAO,
        ]);
    }

    public function podeSerAnalisadoPeloTutor(): bool
    {
        return $this->status === self::STATUS_EM_ANALISE_TUTOR;
    }

    public function podeSerAnalisadoPelaCoordenacao(): bool
    {
        return $this->status === self::STATUS_EM_ANALISE_COORDENACAO;
    }

    public function estaAprovado(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }

    /**
     * Determina o próximo status após uma submissão.
     * Quando o aluno submete, o destino é sempre o tutor —
     * independentemente de a correção ter vindo do tutor
     * ou da coordenação.
     */
    public function proximoStatusAposSubmissao(): string
    {
        return self::STATUS_EM_ANALISE_TUTOR;
    }
}
