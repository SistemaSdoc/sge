<?php

namespace App\Policies;

use App\Models\User;

class ConfirmacaoMatriculaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.viewAny');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.update');
    }

    public function aprovar(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.aprovar');
    }

    public function reprovar(User $user): bool
    {
        return $user->hasPermissionTo('confirmacao-matricula.reprovar');
    }
}