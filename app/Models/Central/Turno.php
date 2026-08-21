<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nome'])]

class Turno extends Model
{
    use HasUuid;

    protected $table = 'turnos';

    public function cursoClasseTurnos()
    {
        return $this->hasMany(CursoClasseTurno::class);
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }
}
