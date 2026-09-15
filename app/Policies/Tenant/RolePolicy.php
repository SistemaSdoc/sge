<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('acessos.viewAny');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('acessos.viewAny')
            && $role->guard_name === 'tenant'
            && $role->name !== 'SuperAdmin';
    }

    public function create(User $user): bool
    {
        return $user->can('acessos.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->view($user, $role) && $user->can('acessos.create');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role) && $role->name !== 'Director';
    }

    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
