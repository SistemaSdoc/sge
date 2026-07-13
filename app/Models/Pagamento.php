<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    use HasUuids;

    protected $table = 'pagamentos';

    protected $fillable = [
        'propina_id',
        'valor_pago',
        'data_pagamento',
        'metodo',
        'comprovativo_path',
        'registado_por',
    ];

    protected $casts = [
        'valor_pago'     => 'decimal:2',
        'data_pagamento' => 'date',
    ];

    protected static function booted(): void
    {
        // recalcula o estado da propina sempre que um pagamento é criado/apagado
        static::created(fn (Pagamento $pagamento) => $pagamento->propina->atualizarEstado());
        static::deleted(fn (Pagamento $pagamento) => $pagamento->propina->atualizarEstado());
    }

    public function propina(): BelongsTo
    {
        return $this->belongsTo(Propina::class);
    }

    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registado_por');
    }
}