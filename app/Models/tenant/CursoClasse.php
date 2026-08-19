<?php

namespace App\Models\tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'classe_id',
    'curso_tutelado_id',
    'nivel_ensino_id',
])]

class CursoClasse extends Pivot
{
    use HasUuid;

    protected $table = 'curso_classe';

    public $incrementing = false;

    public $keyType = 'string';

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function nivelEnsino()
    {
        return $this->belongsTo(NivelEnsino::class);
    }

    public function cursoTutelado()
    {
        return $this->belongsTo(CursoTutelado::class, 'curso_tutelado_id');
    }

    public function turnos()
    {
        return $this->hasMany(CursoClasseTurno::class, 'curso_classe_id');
    }

    public function cursoClasseTurnos()
    {
        return $this->hasMany(CursoClasseTurno::class, 'curso_classe_id');
    }
}
