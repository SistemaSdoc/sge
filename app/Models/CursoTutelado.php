<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'instituicao_curso_id',
    'instituicao_tutora_id',
])]

class CursoTutelado extends Model
{
    use HasUuid;

    protected $table = 'curso_tutelado';

    public function instituicaoCurso()
    {
        return $this->belongsTo(InstituicaoCurso::class, 'instituicao_curso_id');
    }

    public function instituicaoTutora()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_tutora_id');
    }

    public function cursoClasses()
    {
        return $this->hasMany(CursoClasse::class, 'curso_tutelado_id');
    }

    public function classes()
    {
        return $this->belongsToMany(
            Classe::class,
            'curso_classe',
            'curso_tutelado_id',
            'classe_id'
        )
            ->using(CursoClasse::class)
            ->withTimestamps();
    }

    public function professores()
    {
        return $this->belongsToMany(
            Professor::class,
            'curso_tutelado_professor',
            'curso_tutelado_id',
            'professor_id'
        )
            ->using(CursoTuteladoProfessor::class)
            ->withPivot('tipo', 'coordenador')
            ->withTimestamps();
    }
}
