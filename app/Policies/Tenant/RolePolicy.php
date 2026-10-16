<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determina se o usuário pode listar roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('acessos.viewAny');
    }

    /**
     * Determina se o usuário pode consultar uma role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('acessos.viewAny')
            && $role->guard_name === 'tenant'
            && $role->name !== 'SuperAdmin';
    }

    /**
     * Determina se o usuário pode criar roles.
     */
    public function create(User $user): bool
    {
        return $user->can('acessos.create');
    }

    /**
     * Determina se o usuário pode actualizar uma role.
     */
    public function update(User $user, Role $role): bool
    {
        return $this->view($user, $role) && $user->can('acessos.create');
    }

    /**
     * Determina se o usuário pode eliminar uma role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role) && $role->name !== 'Director';
    }

    /**
     * Determina se o usuário pode restaurar uma role.
     */
    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma role.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
