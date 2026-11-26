<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CalendarioProva;
use App\Models\Tenant\User;

class CalendarioProvaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('calendario-prova.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CalendarioProva $calendarioProva): bool
    {
        return $user->can('calendario-prova.view')
            && $calendarioProva->cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('calendario-prova.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CalendarioProva $calendarioProva): bool
    {
        return $user->can('calendario-prova.update')
            && $calendarioProva->cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalendarioProva $calendarioProva): bool
    {
        return $user->can('calendario-prova.delete')
            && $calendarioProva->cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode publicar/despublicar um calendário.
     */
    public function toggleAtivo(User $user, CalendarioProva $calendarioProva): bool
    {
        return $user->can('calendario-prova.toggleAtivo')
            && $calendarioProva->cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CalendarioProva $calendarioProva): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CalendarioProva $calendarioProva): bool
    {
        return false;
    }
}
