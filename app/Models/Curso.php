<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nome', 'duracao_anos', 'descricao', 'status'])]
class Curso extends Model
{
    use HasUuid;

    protected $table = 'cursos';

    protected $primaryKey = 'id';

    public function getStatusTextoAttribute()
    {
        return $this->status == 1 ? 'Activo' : 'Inactivo';
    }

    public function instituicoes()
    {
        return $this->belongsToMany(
            Instituicao::class,
            'instituicao_curso',
            'curso_id',
            'instituicao_id'
        )
            ->using(InstituicaoCurso::class)
            ->withTimestamps();
    }

    public function instituicaoCursos()
    {
        return $this->hasMany(InstituicaoCurso::class);
    }

    public function classes()
    {
        return $this->belongsToMany(
            Classe::class,
            'curso_classe',
            'curso_id',
            'classe_id'
        )
            ->using(CursoClasse::class)
            ->withTimestamps();
    }
}
