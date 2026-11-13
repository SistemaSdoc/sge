<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class DefesaPapPolicy
{
    /**
     * Determina se o utilizador pode definir a data da defesa.
     */
    public function definirData(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso') || $grupoPap->status_aprovacao !== 'aprovado') {
            return false;
        }

        return $user->can('grupopap.definirData')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }
}
