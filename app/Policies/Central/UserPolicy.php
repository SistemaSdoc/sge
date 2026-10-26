<?php

namespace App\Policies\Central;

use App\Models\Central\User;

class UserPolicy
{
    /**
     * Determina se o usuario pode consultar algum usuario.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode consultar outro usuario.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode criar usuarios.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode actualizar outro usuario.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar outro usuario.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar permanentemente outro usuario.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('SuperAdmin');
    }
}
