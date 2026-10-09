<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;

class UserPolicy
{
    /**
     * Determina se o usuário pode listar usuários.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.viewAny');
    }

    /**
     * Determina se o usuário pode consultar o próprio perfil.
     */
    public function viewOwnProfile(User $user, User $target): bool
    {
        return $user->is($target);
    }

    /**
     * Determina se o usuário pode actualizar o próprio perfil.
     */
    public function updateOwnProfile(User $user, User $target): bool
    {
        return $user->is($target);
    }

    /**
     * Determina se o usuário pode consultar o próprio perfil académico.
     */
    public function viewAcademicProfile(User $user, User $target): bool
    {
        return $user->is($target) && $target->hasRole('Aluno');
    }

    /**
     * Determina se o usuário pode consultar outro usuário.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return true;
        }

        if ($target->isDirector() && ! $user->isSuperAdmin() && ! $user->is($target)) {
            return false;
        }

        return $user->can('usuarios.view')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    /**
     * Determina se o usuário pode criar usuários.
     */
    public function create(User $user): bool
    {
        return $user->can('usuarios.create');
    }

    /**
     * Determina se o usuário pode actualizar outro usuário.
     */
    public function update(User $user, User $target): bool
    {
        if (
            ($target->isDirector() && ! $user->isSuperAdmin() && ! $user->is($target))
            || ($user->isSubdirector() && $user->is($target))
        ) {
            return false;
        }

        return $user->can('usuarios.update')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    /**
     * Determina se o usuário pode gerir as permissões individuais de outro usuário.
     */
    public function managePermissions(User $user, User $target): bool
    {
        if (
            ($target->isDirector() && ! $user->isSuperAdmin() && ! $user->is($target))
            || ($user->isSubdirector() && $user->is($target))
        ) {
            return false;
        }

        return $user->can('usuarios.gerir')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    /**
     * Determina se o usuário pode eliminar outro usuário.
     */
    public function delete(User $user, User $target): bool
    {
        // só director (ou super admin) pode remover
        if (! $user->isDirector() && ! $user->isSuperAdmin()) {
            return false;
        }

        // não pode remover a própria conta
        if ($user->is($target)) {
            return false;
        }

        // só super admin remove outro director
        if ($target->isDirector() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->can('usuarios.delete')
            && $user->can('usuarios.gerir')
            && ($user->isSuperAdmin() || $user->instituicao_id === $target->instituicao_id);
    }

    /**
     * Determina se o usuário pode restaurar outro usuário.
     */
    public function restore(User $user, User $target): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente outro usuário.
     */
    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }
}
