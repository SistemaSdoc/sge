<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\User;

class HorarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Aluno', 'Professor', 'Director']);
    }
}
