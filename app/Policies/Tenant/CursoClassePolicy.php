<?php

namespace App\Policies\Tenant;

use App\Models\tenant\CursoClasse;
use App\Models\tenant\User;

class CursoClassePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cursoclasse.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CursoClasse $cursoClasse): bool
    {
        return $user->can('cursoclasse.view')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('cursoclasse.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CursoClasse $cursoClasse): bool
    {
        return $user->can('cursoclasse.update')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CursoClasse $cursoClasse): bool
    {
        return $user->can('cursoclasse.delete')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CursoClasse $cursoClasse): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CursoClasse $cursoClasse): bool
    {
        return false;
    }
}
