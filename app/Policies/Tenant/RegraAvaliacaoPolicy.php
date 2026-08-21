<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\RegraAvaliacao;
use App\Models\Tenant\User;

class RegraAvaliacaoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('regra-avaliacao.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('regra-avaliacao.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return false;
    }
}
