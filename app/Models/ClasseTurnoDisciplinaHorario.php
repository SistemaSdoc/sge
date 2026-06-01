<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'classe_turno_disciplina_id',
    'dia_semana', 'hora_inicio',
    'hora_fim',
])]

class ClasseTurnoDisciplinaHorario extends Model
{
    use HasUuid;

    protected $table = 'classe_turno_disciplina_horarios';

    public $incrementing = false;

    public $keyType = 'string';

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'hora_inicio' => 'datetime:H:i',
            'hora_fim' => 'datetime:H:i',
        ];
    }

    public function classeTurnoDisciplina(): BelongsTo
    {
        return $this->belongsTo(ClasseTurnoDisciplina::class, 'classe_turno_disciplina_id');
    }
}
