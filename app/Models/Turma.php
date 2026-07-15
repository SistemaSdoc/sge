<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'curso_classe_turno_id',
    'nome',
    'max_alunos',
    'ano_lectivo_id',
])]

class Turma extends Model
{
    use HasUuid;

    protected $table = 'turmas';

    protected $primaryKey = 'id';

    public function cursoClasseTurno()
    {
        return $this->belongsTo(CursoClasseTurno::class, 'curso_classe_turno_id');
    }

    public function alunos()
    {
        return $this->belongsToMany(Aluno::class, 'turma_aluno', 'turma_id', 'aluno_id')
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->withTimestamps();
    }

    public function alunosActivos()
    {
        return $this->belongsToMany(Aluno::class, 'turma_aluno', 'turma_id', 'aluno_id')
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->wherePivot('activo', true)
            ->wherePivot('situacao', 'activo')
            ->withTimestamps();
    }

    public function finalistas()
    {
        return $this->belongsToMany(Aluno::class, 'turma_aluno', 'turma_id', 'aluno_id')
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->wherePivot('situacao', 'pap_concluido')
            ->withTimestamps();
    }

    public function professores()
    {
        return $this->belongsToMany(Professor::class, 'turma_disciplina_professor', 'turma_id', 'professor_id')
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('classe_turno_disciplina_id')
            ->withTimestamps();
    }

    public function gruposPap()
    {
        return $this->hasMany(GrupoPap::class, 'turma_id');
    }

    public function turmaDisciplinaProfessor()
    {
        return $this->hasMany(TurmaDisciplinaProfessor::class, 'turma_id');
    }

    public function turmaAlunos()
    {
        return $this->hasMany(TurmaAluno::class, 'turma_id');
    }

    public function anoLectivo()
    {
        return $this->belongsTo(AnoLectivo::class);
    }
}
