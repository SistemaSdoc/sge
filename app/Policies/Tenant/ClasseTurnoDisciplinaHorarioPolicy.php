<?php

namespace App\Policies\Tenant;

use App\Models\tenant\ClasseTurnoDisciplinaHorario;
use App\Models\tenant\User;

class ClasseTurnoDisciplinaHorarioPolicy
{
    /**
     * Determina se o utilizador pode criar horários para uma disciplina numa turma.
     *
     * Apenas Director, Subdirector e Secretaria podem executar esta ação.
     */
    public function create(User $user): bool
    {
        return $user->instituicao_id !== null
            && $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']);
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function update(User $user, ClasseTurnoDisciplinaHorario $horario): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function delete(User $user, ClasseTurnoDisciplinaHorario $horario): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, ClasseTurnoDisciplinaHorario $horario): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, ClasseTurnoDisciplinaHorario $horario): bool
    {
        return false;
    }
}
