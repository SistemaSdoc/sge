<?php

namespace App\Policies;

use App\Models\SolicitacaoEdicaoPauta;
use App\Models\User;

class SolicitacaoEdicaoPautaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('solicitacao-edicao-pauta.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return $user->can('solicitacao-edicao-pauta.view', $solicitacaoEdicaoPauta);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('solicitacao-edicao-pauta.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return $user->can('solicitacao-edicao-pauta.update', $solicitacaoEdicaoPauta);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }
}
