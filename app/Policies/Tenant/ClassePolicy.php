<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Classe;
use App\Models\Tenant\User;

class ClassePolicy
{
    /**
     * Determina se o usuário pode listar as classes gerais.
     *
     * As classes são dados curriculares gerais e não pertencem a uma
     * instituição específica. Por isso, a verificação é apenas por permissão.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('classes.viewAny');
    }

    /**
     * Determina se o usuário pode consultar uma classe geral específica.
     */
    public function view(User $user, Classe $classe): bool
    {
        return $user->can('classes.view');
    }

    /**
     * Determina se o usuário pode criar classes gerais.
     */
    public function create(User $user): bool
    {
        return $user->can('classes.create');
    }

    /**
     * Determina se o usuário pode actualizar uma classe geral.
     */
    public function update(User $user, Classe $classe): bool
    {
        return $user->can('classes.update');
    }

    /**
     * Determina se o usuário pode eliminar uma classe geral.
     */
    public function delete(User $user, Classe $classe): bool
    {
        return $user->can('classes.delete');
    }

    /**
     * Determina se o usuário pode restaurar uma classe geral.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function restore(User $user, Classe $classe): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma classe geral.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function forceDelete(User $user, Classe $classe): bool
    {
        return false;
    }
}
