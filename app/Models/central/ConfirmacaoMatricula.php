<?php

namespace App\Models\central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfirmacaoMatricula extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'confirmacao_matricula';

    protected $fillable = [
        'aluno_id',
        'ano_lectivo_atual_id',
        'ano_lectivo_proximo_id',
        'turma_atual_id',
        'turma_nova_id',
        'status',
        'data_confirmacao',
        'confirmado_por',
        'observacoes',
    ];

    protected $casts = [
        'data_confirmacao' => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════════
    // RELACIONAMENTOS
    // ═══════════════════════════════════════════════════════════════

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function anoLectivoAtual(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_atual_id');
    }

    public function anoLectivoProximo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_proximo_id');
    }

    public function turmaAtual(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_atual_id');
    }

    public function turmaNova(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_nova_id');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function scopeConfirmadas($query)
    {
        return $query->where('status', 'confirmada');
    }

    public function scopeNaoCompareceu($query)
    {
        return $query->where('status', 'nao_compareceu');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('status', 'cancelada');
    }

    public function scopeDoAluno($query, $alunoId)
    {
        return $query->where('aluno_id', $alunoId);
    }

    public function scopeDoAnoProximo($query, $anoLectivoId)
    {
        return $query->where('ano_lectivo_proximo_id', $anoLectivoId);
    }

    public function scopeDoAnoAtual($query, $anoLectivoId)
    {
        return $query->where('ano_lectivo_atual_id', $anoLectivoId);
    }

    public function scopeDaTurmaNova($query, $turmaId)
    {
        return $query->where('turma_nova_id', $turmaId);
    }

    // ═══════════════════════════════════════════════════════════════
    // ACCESSORS / MUTATORS
    // ═══════════════════════════════════════════════════════════════

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmada' => 'Confirmada',
            'nao_compareceu' => 'Não Compareceu',
            'cancelada' => 'Cancelada',
            default => 'Desconhecido',
        };
    }

    public function getTransicaoAttribute(): string
    {
        return "{$this->turmaAtual->nome} → {$this->turmaNova->nome}";
    }
}
