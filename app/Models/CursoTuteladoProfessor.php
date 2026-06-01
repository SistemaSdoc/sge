<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['curso_tutelado_id', 'professor_id', 'tipo'])]
class CursoTuteladoProfessor extends Pivot
{
    use HasUuid;

    protected $table = 'curso_tutelado_professor';

    public $incrementing = false;

    public $keyType = 'string';

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function cursoTutelado()
    {
        return $this->belongsTo(CursoTutelado::class);
    }
}
