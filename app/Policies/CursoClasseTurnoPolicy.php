<?php

namespace App\Policies;

use App\Models\CursoClasseTurno;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CursoClasseTurnoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cursoclasseturno.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.view')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('cursoclasseturno.create') && $user->instituicao_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.update')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.delete')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return false;
    }
}
