<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'turma_id',
    'aluno_id',
    'ano_lectivo',
    'activo',
])]

class TurmaAluno extends Pivot
{
    use HasUuid;

    protected $table = 'turma_aluno';

    public $incrementing = false;

    public $keyType = 'string';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ano_lectivo' => 'integer',
        ];
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'turma_aluno_id', 'id');
    }

    // Helper de conveniência para acessar o ano lectivo sem duplicar dados
    public function anoLectivo()
    {
        return $this->hasOneThrough(
            AnoLectivo::class,
            Turma::class,
            'id',            // FK em turmas
            'id',            // FK em ano_lectivos
            'turma_id',       // FK local em turma_aluno
            'ano_lectivo_id'  // FK local em turmas
        );
    }
}
