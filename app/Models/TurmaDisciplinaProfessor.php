<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['classe_turno_disciplina_id', 'turma_id', 'professor_id'])]
class TurmaDisciplinaProfessor extends Pivot
{
    use HasUuid;

    protected $table = 'turma_disciplina_professor';

    public $incrementing = false;

    public $keyType = 'string';

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function classeTurnoDisciplina()
    {
        return $this->belongsTo(ClasseTurnoDisciplina::class, 'classe_turno_disciplina_id');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'turma_disciplina_professor_id');
    }
}
