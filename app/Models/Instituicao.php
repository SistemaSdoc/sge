<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nome',
    'sigla',
    'tipo',
    'email',
    'telefone',
    'provincia',
    'endereco',
    'status',
    'logo',
    'descricao',
])]

class Instituicao extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'instituicoes';

    protected $primaryKey = 'id';

    public function getStatusTextoAttribute()
    {
        return $this->status == 1 ? 'Activo' : 'Inactivo';
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cursos()
    {
        return $this->belongsToMany(
            Curso::class,
            'instituicao_curso',
            'instituicao_id',
            'curso_id'
        )
            ->using(InstituicaoCurso::class)
            ->withTimestamps();
    }

    public function instituicaoCursos()
    {
        return $this->hasMany(InstituicaoCurso::class);
    }

    public function cursosTutelados()
    {
        return $this->hasMany(CursoTutelado::class, 'instituicao_tutora_id');
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($childType === 'cursoTutelado') {
            return CursoTutelado::whereHas(
                'instituicaoCurso',
                fn ($q) => $q->where('instituicao_id', $this->id)
            )->findOrFail($value);
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
