<?php

namespace App\Policies;

use App\Models\User;

class ColegioPolicy
{
    /**
     * Apenas instituições do tipo "Instituto" podem ver os colégios tutelados.
     */
    public function viewAny(User $user): bool
    {
        // Verificar permissão de Spatie (já definida no seeder)
        if (! $user->hasPermissionTo('colegios.viewAny')) {
            return false;
        }

        // Apenas Institutos podem ver colégios
        return $user->instituicao?->tipo === 'instituto';
    }
}
