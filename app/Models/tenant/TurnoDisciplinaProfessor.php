<?php

namespace App\Models\tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'professor_id',
    'classe_turno_disciplina_id',
])]

class TurnoDisciplinaProfessor extends Pivot
{
    use HasUuid;

    protected $table = 'turno_disciplina_professor';

    public $incrementing = false;

    public $keyType = 'string';

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function classeTurnoDisciplina()
    {
        return $this->belongsTo(ClasseTurnoDisciplina::class);
    }
}
