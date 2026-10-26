<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Guarda os dados apresentados no quadro no momento em que este foi gerado. */
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

    public function turmaAluno(): BelongsTo
    {
        return $this->belongsTo(TurmaAluno::class);
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }
}
