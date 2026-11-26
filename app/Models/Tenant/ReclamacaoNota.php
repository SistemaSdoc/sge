<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nota_id',
    'solicitado_por',
    'decidido_por',
    'componente',
    'nota_original',
    'nota_reclamada',
    'nota_corrigida',
    'justificativa',
    'status',
    'parecer',
    'decidido_em',
])]
class ReclamacaoNota extends Model
{
    use HasUuid;

    protected $table = 'reclamacoes_notas';

    protected function casts(): array
    {
        return [
            'nota_original' => 'decimal:2',
            'nota_reclamada' => 'decimal:2',
            'nota_corrigida' => 'decimal:2',
            'decidido_em' => 'datetime',
        ];
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(Nota::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function decisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoReclamacaoNota::class, 'reclamacao_nota_id');
    }

    public function estaAberta(): bool
    {
        return in_array($this->status, ['pendente', 'em_analise', 'aguarda_informacao'], true);
    }
}
