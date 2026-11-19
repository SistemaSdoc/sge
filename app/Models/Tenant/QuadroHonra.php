<?php

namespace App\Models\Tenant;

use App\Models\Central\AnoLectivo;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Regista uma geração de Quadro de Honra por ano e âmbito. */
#[Fillable([
    'ano_lectivo_id',
    'tipo',
    'escopo',
    'periodo',
    'media_minima',
    'limite_alunos',
    'gerado_por',
    'gerado_em',
])]
class QuadroHonra extends Model
{
    use HasUuid;

    protected $table = 'quadros_honra';

    protected function casts(): array
    {
        return [
            'tipo' => 'string',
            'periodo' => 'integer',
            'media_minima' => 'decimal:2',
            'limite_alunos' => 'integer',
            'gerado_em' => 'datetime',
        ];
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function geradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(QuadroHonraItem::class);
    }
}
