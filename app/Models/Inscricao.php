<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_classe_turno_id', 'candidato_id', 'status', 'nota_teste'])]
class Inscricao extends Model
{
    use HasUuid;

    protected $table = 'inscricoes';

    protected $primaryKey = 'id';

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function cursoClasseTurno()
    {
        return $this->belongsTo(CursoClasseTurno::class, 'curso_classe_turno_id');
    }
}
