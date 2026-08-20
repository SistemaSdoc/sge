<?php

namespace App\Policies\Tenant;

use App\Models\tenant\User;

class AcessManagementPolicy
{
    /**
     * Determina se o utilizador pode acessar a gestão de roles e permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('acessos.viewAny');
    }

    /**
     * Determina se o utilizador pode alterar roles e permissions
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('acessos.create');
    }
}
