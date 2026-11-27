<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Turno;
use App\Models\Tenant\User;

class TurnoPolicy
{
    /**
     * Determina se o usuário pode listar turnos.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('turnos.viewAny');
    }

    /**
     * Determina se o usuário pode consultar um turno.
     */
    public function view(User $user, Turno $turno): bool
    {
        return $user->can('turnos.view');
    }

    /**
     * Determina se o usuário pode criar turnos.
     */
    public function create(User $user): bool
    {
        return $user->can('turnos.create');
    }

    /**
     * Determina se o usuário pode actualizar um turno.
     */
    public function update(User $user, Turno $turno): bool
    {
        return $user->can('turnos.update');
    }

    /**
     * Determina se o usuário pode eliminar um turno.
     */
    public function delete(User $user, Turno $turno): bool
    {
        return $user->can('turnos.delete');
    }

    /**
     * Determina se o usuário pode restaurar um turno.
     */
    public function restore(User $user, Turno $turno): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente um turno.
     */
    public function forceDelete(User $user, Turno $turno): bool
    {
        return false;
    }
}
