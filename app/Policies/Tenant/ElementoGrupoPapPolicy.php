<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class ElementoGrupoPapPolicy
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
        return $user->can('elementogrupopap.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return $user->can('elementogrupopap.view')
            && $this->pertenceAoGrupoOuTutela($user, $elementoGrupoPap->grupoPap);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('elementogrupopap.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return $user->can('elementogrupopap.update')
            && $this->pertenceAoGrupoOuTutela($user, $elementoGrupoPap->grupoPap);
    }

    public function atualizarNota(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        $grupoPap = $elementoGrupoPap->grupoPap;

        // Data de defesa tem de estar definida e já ter chegado
        if (is_null($grupoPap->data_defesa) || $grupoPap->data_defesa->isFuture()) {
            return false;
        }

        // Tem de existir pelo menos um jurado na banca
        if ($grupoPap->jurados()->doesntExist()) {
            return false;
        }

        return $user->can('elementogrupopap.atualizarNota')
            && $this->pertenceAoGrupoOuTutela($user, $grupoPap);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return $user->can('elementogrupopap.delete')
            && $this->pertenceAoGrupoOuTutela($user, $elementoGrupoPap->grupoPap);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return false;
    }
}
