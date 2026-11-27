<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\RegraAvaliacao;
use App\Models\Tenant\User;

class RegraAvaliacaoPolicy
{
    /**
     * Determina se o usuário pode listar regras de avaliação.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('regra-avaliacao.viewAny');
    }

    /**
     * Determina se o usuário pode consultar uma regra de avaliação.
     */
    public function view(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.view');
    }

    /**
     * Determina se o usuário pode criar regras de avaliação.
     */
    public function create(User $user): bool
    {
        return $user->can('regra-avaliacao.create');
    }

    /**
     * Determina se o usuário pode actualizar uma regra de avaliação.
     */
    public function update(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.update');
    }

    /**
     * Determina se o usuário pode eliminar uma regra de avaliação.
     */
    public function delete(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return $user->can('regra-avaliacao.delete');
    }

    /**
     * Determina se o usuário pode restaurar uma regra de avaliação.
     */
    public function restore(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma regra de avaliação.
     */
    public function forceDelete(User $user, RegraAvaliacao $regraAvaliacao): bool
    {
        return false;
    }
}
