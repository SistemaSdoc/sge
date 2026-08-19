<?php

namespace App\Models\tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propina extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'propinas';

    protected $fillable = [
        'aluno_id',
        'ano_lectivo_id',
        'item_pagavel_id',
        'mes',
        'valor_devido',
        'data_vencimento',
        'estado',
    ];

    protected $casts = [
        'valor_devido' => 'decimal:2',
        'data_vencimento' => 'date',
        'mes' => 'integer',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function itemPagavel(): BelongsTo
    {
        return $this->belongsTo(ItemPagavel::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Soma total já pago para esta propina.
     */
    public function totalPago(): float
    {
        return (float) $this->pagamentos()->sum('valor_pago');
    }

    /**
     * Recalcula e persiste o estado com base nos pagamentos registados.
     * Chamar sempre depois de criar/editar/apagar um Pagamento.
     */
    public function atualizarEstado(): void
    {
        if ($this->estado === 'isento') {
            return;
        }

        $totalPago = $this->totalPago();

        $novoEstado = match (true) {
            $totalPago <= 0 => now()->gt($this->data_vencimento) ? 'atrasado' : 'pendente',
            $totalPago < $this->valor_devido => 'parcial',
            default => 'pago',
        };

        if ($novoEstado !== $this->estado) {
            $this->update(['estado' => $novoEstado]);
        }
    }
}
