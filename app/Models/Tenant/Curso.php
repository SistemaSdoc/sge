<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'cursos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nome',
        'descricao',
        'duracao_anos',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function getStatusTextoAttribute(): string
    {
        return $this->status === 1 ? 'Activo' : 'Inactivo';
    }

    public function instituicaoCursos()
    {
        return $this->hasMany(InstituicaoCurso::class, 'curso_id');
    }
}