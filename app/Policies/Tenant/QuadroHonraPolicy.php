<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;

/** Todos os perfis da instituição consultam os quadros; Director e Secretaria ajustam os critérios. */
class QuadroHonraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->instituicao_id !== null;
    }

    public function view(User $user, QuadroHonra $quadroHonra): bool
    {
        return $user->instituicao_id !== null
            && $quadroHonra->tipo === 'geral';
    }

    public function generate(User $user): bool
    {
        return $user->instituicao_id !== null
            && $user->hasRole('Director');
    }

    public function updateCriteria(User $user): bool
    {
        return $user->instituicao_id !== null
            && $user->hasAnyRole(['Director', 'Secretaria']);
    }
}
