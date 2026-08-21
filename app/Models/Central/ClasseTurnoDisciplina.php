<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'curso_classe_turno_id',
    'disciplina_id',
    'ano_lectivo_id',
    'carga_horaria',
    'tem_professor',
])]

class ClasseTurnoDisciplina extends Pivot
{
    use HasUuid;

    protected $table = 'classe_turno_disciplina';

    public $incrementing = false;

    public $keyType = 'string';

    public function cursoClasseTurno()
    {
        return $this->belongsTo(CursoClasseTurno::class, 'curso_classe_turno_id');
    }

    public function anoLectivo()
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function turmaDisciplinaProfessores()
    {
        return $this->hasMany(TurmaDisciplinaProfessor::class, 'classe_turno_disciplina_id');
    }

    public function professores()
    {
        return $this->belongsToMany(
            Professor::class,
            'turma_disciplina_professor',
            'classe_turno_disciplina_id',
            'professor_id'
        )
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('turma_id')
            ->withTimestamps();
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'classe_turno_disciplina_id');
    }

    public function horarios()
    {
        return $this->hasMany(ClasseTurnoDisciplinaHorario::class, 'classe_turno_disciplina_id');
    }
}
