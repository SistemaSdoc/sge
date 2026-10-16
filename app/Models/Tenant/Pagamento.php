<?php

namespace App\Models\Tenant;

use App\Traits\HasSearch;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagamento extends Model
{
    use HasSearch, HasUuid, SoftDeletes;

    protected array $searchable = ['referencia', 'numero_recibo', 'metodo', 'observacoes', 'aluno.user.nome'];

    protected $table = 'pagamentos';

    protected $fillable = [
        'aluno_id', 'instituicao_id', 'registado_por',
        'data_pagamento', 'valor_total', 'metodo', 'referencia', 'observacoes',
        'recibo_path', 'numero_recibo',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor_total' => 'decimal:2',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class)->withTrashed();
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PagamentoItem::class);
    }

    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registado_por')->withTrashed();
    }
}
