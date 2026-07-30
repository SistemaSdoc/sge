<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ConfirmacaoMatriculaPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function aprovar(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.aprovar');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function reprovar(User $user): bool
    {
        try {
            return $user->hasPermissionTo('confirmacao-matricula.reprovar');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
