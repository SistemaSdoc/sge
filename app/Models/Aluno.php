<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'inscricao_id',
    'matricula',
    'situacao',
])]

class Aluno extends Model
{
    use HasUuid;

    protected $table = 'alunos';

    protected $primaryKey = 'id';

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('situacao', 'activo');
    }

    public function scopeFinalistas($query)
    {
        return $query->where('situacao', 'finalista');
    }

    public function scopeConcluidos($query)
    {
        return $query->where('situacao', 'concluido');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot('ano_lectivo')
            ->using(TurmaAluno::class)
            ->withTimestamps();
    }

    public function turmaActual()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot('ano_lectivo', 'activo', true)
            ->wherePivot('ano_lectivo', 'situacao', ['activo', 'pap_concluido'], date('Y'))
            ->with('classe:id,nome');
    }

    public function historicoTurmas()
    {
        return $this->turmas()
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->orderByPivot('ano_lectivo', 'asc');
    }

    public function grupoPap()
    {
        return $this->hasOneThrough(
            GrupoPap::class,
            ElementoGrupoPap::class,
            'aluno_id',
            'id',
            'id',
            'grupo_pap_id'
        );
    }
}
