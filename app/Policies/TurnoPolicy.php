<?php

namespace App\Policies;

use App\Models\Turno;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TurnoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('turnos.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Turno $turno): bool
    {
        return $user->can('turnos.view') && $turno->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('turnos.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Turno $turno): bool
    {
        return $user->can('turnos.update') && $turno->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Turno $turno): bool
    {
        return $user->can('turnos.delete') && $turno->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Turno $turno): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Turno $turno): bool
    {
        return false;
    }
}
