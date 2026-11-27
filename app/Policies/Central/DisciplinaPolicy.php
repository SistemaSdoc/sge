<?php

namespace App\Policies\Central;

use App\Models\Central\Disciplina;
use App\Models\Central\User;

class DisciplinaPolicy
{
    /**
     * Determina se o usuario pode consultar alguma disciplina.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode consultar a disciplina.
     */
    public function view(User $user, Disciplina $disciplina): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode criar disciplinas.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode actualizar a disciplina.
     */
    public function update(User $user, Disciplina $disciplina): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode arquivar a disciplina.
     */
    public function delete(User $user, Disciplina $disciplina): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode restaurar a disciplina arquivada.
     */
    public function restore(User $user, Disciplina $disciplina): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar permanentemente a disciplina.
     */
    public function forceDelete(User $user, Disciplina $disciplina): bool
    {
        return $user->hasRole('SuperAdmin');
    }
}
