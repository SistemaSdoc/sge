<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;

class SugestaoTemaPapPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function gerir(User $user): bool
    {
        return $user->can('sugestaotema.gerir');
    }

    /**
     * Determine whether the user can approve a suggestion.
     */
    public function aprovar(User $user, SugestaoTemaPap $sugestao): bool
    {
        return $user->can('sugestaotema.aprovar');
    }

    /**
     * Determine whether the user can resubmit a suggestion after improvements have been requested.
     */
    public function resubmeter(User $user, SugestaoTemaPap $sugestao): bool
    {
        return $sugestao->isMelhoriasolicitada()
            && $user->can('sugestaotema.gerir');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SugestaoTemaPap $sugestaoTemaPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SugestaoTemaPap $sugestaoTemaPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SugestaoTemaPap $sugestaoTemaPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SugestaoTemaPap $sugestaoTemaPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SugestaoTemaPap $sugestaoTemaPap): bool
    {
        return false;
    }
}
