<?php

namespace App\Policies\Tenant;

use App\Models\tenant\Classe;
use App\Models\tenant\User;

class ClassePolicy
{
    /**
     * Determina se o utilizador pode listar classes.
     *
     * classes são genéricos e não pertencem a nenhuma instituição,
     * por isso a verificação é apenas por permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('classes.viewAny');
    }

    /**
     * Determina se o utilizador pode ver uma classe específica.
     */
    public function view(User $user, Classe $curso): bool
    {
        return $user->can('classes.view');
    }

    /**
     * Determina se o utilizador pode criar classes.
     */
    public function create(User $user): bool
    {
        return $user->can('classes.create');
    }

    /**
     * Determina se o utilizador pode editar uma classe.
     */
    public function update(User $user, Classe $classe): bool
    {
        return $user->can('classes.update');
    }

    /**
     * Determina se o utilizador pode apagar um curso.
     */
    public function delete(User $user, Classe $classe): bool
    {
        return $user->can('classes.delete');
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Classe $classe): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Classe $classe): bool
    {
        return false;
    }
}
