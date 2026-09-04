<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class BancaJuriPapPolicy
{
    private function pertenceAoGrupoOuTutela(User $user, GrupoPap $grupoPap): bool
    {
        $instituicaoId = $user->instituicao_id;

        return $instituicaoId !== null
            && $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicao_tutora_id === $instituicaoId;
    }

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
            && $this->pertenceAoGrupoOuTutela($user, $bancaJuriPap->grupoPap);
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
            && $this->pertenceAoGrupoOuTutela($user, $grupoPap);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return $user->can('bancajuripap.update')
            && $this->pertenceAoGrupoOuTutela($user, $bancaJuriPap->grupoPap);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BancaJuriPap $bancaJuriPap): bool
    {
        return $user->can('bancajuripap.delete')
            && $this->pertenceAoGrupoOuTutela($user, $bancaJuriPap->grupoPap);
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
