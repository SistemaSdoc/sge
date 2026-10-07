<?php

namespace App\Models\Tenant;

use App\Traits\HasSearch;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nome'])]

class Turno extends Model
{
    use HasSearch, HasUuid;

    protected array $searchable = ['nome'];

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
