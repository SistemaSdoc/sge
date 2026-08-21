<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Aviso;
use App\Models\Tenant\User;

class AvisoPolicy
{
    /**
     * Determina se o utilizador pode listar avisos.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('avisos.viewAny');
    }

    /**
     * Determina se o utilizador pode ver um aviso específico.
     *
     * Requer permission e que o aviso pertença à sua instituição.
     */
    public function view(User $user, Aviso $aviso): bool
    {
        return $user->can('avisos.view') && $aviso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode criar avisos.
     */
    public function create(User $user): bool
    {
        return $user->can('avisos.create');
    }

    /**
     * Determina se o utilizador pode editar um aviso.
     *
     * Requer permission e que o aviso pertença à sua instituição.
     */
    public function update(User $user, Aviso $aviso): bool
    {
        return $user->can('avisos.update') && $aviso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode apagar um aviso.
     *
     * Requer permission e que o aviso pertença à sua instituição.
     */
    public function delete(User $user, Aviso $aviso): bool
    {
        return $user->can('avisos.delete') && $aviso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Aviso $aviso): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Aviso $aviso): bool
    {
        return false;
    }
}
