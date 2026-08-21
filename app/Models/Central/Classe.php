<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nome', 'nivel_ensino', 'ordem'])]
class Classe extends Model
{
    use HasUuid;

    protected $table = 'classes';

    protected $primaryKey = 'id';

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'classe_id');
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'classe_curso');
    }
}
