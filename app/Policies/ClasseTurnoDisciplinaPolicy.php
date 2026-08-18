<?php

namespace App\Policies;

use App\Models\ClasseTurnoDisciplina;
use App\Models\User;

class ClasseTurnoDisciplinaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('classeturnodisciplina.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        /*return $user->can('classeturnodisciplina.view')
            && $classeTurnoDisciplina->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;*/

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('classeturnodisciplina.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return $user->can('classeturnodisciplina.update')
            && $classeTurnoDisciplina->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return $user->can('classeturnodisciplina.delete')
            && $classeTurnoDisciplina->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return false;
    }
}
