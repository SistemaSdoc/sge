<?php

namespace App\Models\central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'curso_classe_id',
    'turno_id',
])]

class CursoClasseTurno extends Pivot
{
    use HasUuid;

    protected $table = 'curso_classe_turno';

    public $incrementing = false;

    public $keyType = 'string';

    public function cursoClasse()
    {
        return $this->belongsTo(CursoClasse::class, 'curso_classe_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function classeTurnoDisciplinas()
    {
        return $this->hasMany(ClasseTurnoDisciplina::class, 'curso_classe_turno_id');
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class, 'curso_classe_turno_id');
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'curso_classe_turno_id');
    }

    public function cursoTutelado()
    {
        return $this->belongsTo(CursoTutelado::class);
    }
}
