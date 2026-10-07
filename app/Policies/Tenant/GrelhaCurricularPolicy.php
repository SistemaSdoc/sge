<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;

class GrelhaCurricularPolicy
{
    /**
     * Determina se o usuário pode consultar a própria grelha curricular.
     *
     * A operação é exclusiva de alunos e os dados devem ser filtrados pelo
     * aluno autenticado no controller ou serviço responsável.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('grelha.viewAny') && $user->hasRole('Aluno');
    }
}
