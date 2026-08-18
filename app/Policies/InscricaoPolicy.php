<?php

namespace App\Policies;

use App\Models\Inscricao;
use App\Models\User;

class InscricaoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inscricoes.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.view')
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('inscricoes.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.update')
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    public function cancelar(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.cancelar', $inscricao) // ou a tua lógica de permissão
            && $inscricao->status !== 'cancelado'
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    public function reativar(User $user, Inscricao $inscricao): bool
    {
        return $inscricao->status === 'cancelado'
            && $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']);
    }
}
