<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class BancaJuriPapPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('bancajuripap.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return $user->can('bancajuripap.view')
            && $bancaJuriPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, GrupoPap $grupoPap): bool
    {
        // Tem de ter data de defesa definida
        if (is_null($grupoPap->data_defesa)) {
            return false;
        }

        return $user->can('bancajuripap.create')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return $user->can('bancajuripap.update')
            && $bancaJuriPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return $user->can('bancajuripap.delete')
            && $bancaJuriPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return false;
    }
}
