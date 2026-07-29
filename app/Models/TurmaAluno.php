<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'turma_id',
    'aluno_id',
    'activo',
    'estado_matricula',
    'resultado_academico',
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

    public function anoLectivo()
    {
        return $this->hasOneThrough(
            AnoLectivo::class,
            Turma::class,
            'id',
            'id',
            'turma_id',
            'ano_lectivo_id'
        );
    }
    
}
