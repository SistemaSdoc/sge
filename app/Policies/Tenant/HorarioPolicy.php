<?php

namespace App\Policies\Tenant;

use App\Models\tenant\User;

class HorarioPolicy
{
    /**
     * Beta: apenas Aluno e Professor podem ver o menu/página de Horários.
     * Sem model dedicado ainda — verificação genérica por role.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Aluno', 'Professor']);
    }
}
