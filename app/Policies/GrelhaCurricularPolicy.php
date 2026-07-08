<?php

namespace App\Policies;

// ajusta o nome do model se for diferente
use App\Models\User;

class GrelhaCurricularPolicy
{
    /**
     * Determina se o utilizador pode ver a sua grelha curricular.
     *
     * Exclusivo de alunos. O controller filtra os dados
     * pelo aluno autenticado via query.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('grelha.viewAny')
            && $user->hasRole('Aluno');
    }
}
