<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'especialidade',
])]

class Professor extends Model
{
    use HasUuid;

    protected $table = 'professores';

    protected $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_disciplina_professor', 'professor_id', 'turma_id')
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('classe_turno_disciplina_id')
            ->withTimestamps();
    }

    public function disciplinas()
    {
        return $this->belongsToMany(ClasseTurnoDisciplina::class, 'turma_disciplina_professor', 'professor_id', 'classe_turno_disciplina_id')
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('turma_id')
            ->withTimestamps();
    }

    public function gruposPap()
    {
        return $this->hasMany(GrupoPap::class, 'professor_tutor_id');
    }

    public function turnoDisciplina()
    {
        return $this->hasMany(GrupoPap::class, 'professor_tutor_id');
    }

    public function turmaDisciplinaProfessor()
    {
        return $this->hasMany(TurmaDisciplinaProfessor::class, 'professor_id');
    }

    public function classeTurnoDisciplinas()
    {
        return $this->belongsToMany(
            ClasseTurnoDisciplina::class,
            'turma_disciplina_professor',
            'professor_id',
            'classe_turno_disciplina_id'
        )
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('turma_id')
            ->withTimestamps();
    }

    public function cursosTutelados()
    {
        return $this->belongsToMany(CursoTutelado::class, 'curso_tutelado_professor')
            ->using(CursoTuteladoProfessor::class)
            ->withPivot('tipo', 'coordenador')
            ->withTimestamps();
    }
}
