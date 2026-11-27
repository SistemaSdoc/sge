<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable([
    'nome',
    'sigla',
    'componente',
    'carga_horaria',
])]

class Disciplina extends Model
{
    use CentralConnection, HasUuid;

    protected $table = 'disciplinas';

    protected $primaryKey = 'id';

    public function classeTurnoDisciplinas()
    {
        return $this->hasMany(ClasseTurnoDisciplina::class);
    }

    public function professores()
    {
        return $this->belongsToMany(
            Professor::class,
            'turno_disciplina_professor',
            'disciplina_id',
            'professor_id'
        );
    }
}
