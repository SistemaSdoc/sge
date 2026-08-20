<?php

namespace App\Policies\Tenant;

use App\Models\tenant\ElementoGrupoPap;
use App\Models\tenant\User;

class ElementoGrupoPapPolicy
{
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
            && $elementoGrupoPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
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
            && $elementoGrupoPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
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
            && $elementoGrupoPap->grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ElementoGrupoPap $elementoGrupoPap): bool
    {
        return $user->can('elementogrupopap.delete')
            && $elementoGrupoPap->grupoPap->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
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
