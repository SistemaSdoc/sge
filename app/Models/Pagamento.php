<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagamento extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'pagamentos';

    protected $fillable = [
        'aluno_id', 'instituicao_id', 'registado_por',
        'data_pagamento', 'valor_total', 'metodo', 'referencia', 'observacoes',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor_total' => 'decimal:2',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PagamentoItem::class);
    }

    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registado_por');
    }
}
