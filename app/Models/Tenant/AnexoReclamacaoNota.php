<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reclamacao_nota_id',
    'caminho',
    'nome_original',
    'mime_type',
    'tamanho_bytes',
])]
class AnexoReclamacaoNota extends Model
{
    use HasUuid;

    protected $table = 'anexos_reclamacoes_notas';

    protected function casts(): array
    {
        return [
            'tamanho_bytes' => 'integer',
        ];
    }

    public function reclamacaoNota(): BelongsTo
    {
        return $this->belongsTo(ReclamacaoNota::class);
    }
}
