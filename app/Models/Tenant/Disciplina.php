<?php

namespace App\Models\Tenant;

use App\Models\Central\Disciplina as CentralDisciplina;

/**
 * Compatibilidade para código tenant legado; os dados vivem na conexão central.
 */
class Disciplina extends CentralDisciplina
{
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
