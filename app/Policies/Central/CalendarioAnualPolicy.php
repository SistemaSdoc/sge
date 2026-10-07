<?php

namespace App\Policies\Central;

use App\Models\Central\CalendarioAnual;
use App\Models\Central\User;

class CalendarioAnualPolicy
{
    /**
     * Determina se o usuario pode consultar algum calendario anual.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode consultar o calendario anual.
     */
    public function view(User $user, CalendarioAnual $calendario): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode criar calendarios anuais.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode actualizar o calendario anual.
     */
    public function update(User $user, CalendarioAnual $calendario): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar o calendario anual.
     */
    public function delete(User $user, CalendarioAnual $calendario): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar permanentemente o calendario anual.
     */
    public function forceDelete(User $user, CalendarioAnual $calendario): bool
    {
        return $user->hasRole('SuperAdmin');
    }
}
