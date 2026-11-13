<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preserva o retrato dos dados usado no quadro no momento da geração,
 * mesmo que a matrícula ou as notas do aluno mudem posteriormente.
 */
#[Fillable([
    'quadro_honra_id',
    'turma_aluno_id',
    'aluno_id',
    'posicao',
    'nome_aluno',
    'matricula',
    'classe',
    'curso',
    'turma',
    'media',
    'faltas_total',
    'notas_abaixo_dez',
])]
class QuadroHonraItem extends Model
{
    use HasUuid;

    protected $table = 'quadro_honra_itens';

    protected function casts(): array
    {
        return [
            'posicao' => 'integer',
            'media' => 'decimal:2',
            'faltas_total' => 'integer',
            'notas_abaixo_dez' => 'integer',
        ];
    }

    public function quadroHonra(): BelongsTo
    {
        return $this->belongsTo(QuadroHonra::class);
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }
}
