<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        if ($this->isProtectedDirectorTarget($user, $target)) {
            return false;
        }

        return $user->can('usuarios.view')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($this->isProtectedDirectorTarget($user, $target) || $this->isSelfProtectedTarget($user, $target)) {
            return false;
        }

        return $user->can('usuarios.update')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    public function delete(User $user, User $target): bool
    {
        if ($this->isProtectedDirectorTarget($user, $target) || $this->isSelfProtectedTarget($user, $target)) {
            return false;
        }

        return $user->can('usuarios.delete')
            && ($user->getKey() !== $target->getKey()) && $this->canManage($user, $target);
    }

    public function restore(User $user, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }

    private function canManage(User $user, User $target): bool
    {
        return $user->can('usuarios.gerir')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    private function isProtectedDirectorTarget(User $user, User $target): bool
    {
        return $target->isDirector() && ! $user->isSuperAdmin() && ! $user->is($target);
    }

    private function isSelfProtectedTarget(User $user, User $target): bool
    {
        return $user->isSubdirector() && $user->is($target);
    }
}
