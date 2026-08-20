<?php

namespace App\Policies\Tenant;

use App\Models\tenant\User;

class ColegioPolicy
{
    /**
     * Determina se o utilizador pode ver a lista de colégios.
     *
     * Apenas utilizadores com a permissão correta e pertencentes a uma
     * instituição do tipo "instituto" podem ver os colégios tutelados.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('colegios.viewAny')
            && $user->instituicao_id !== null
            && $user->instituicao?->tipo === 'instituto';
    }
}
