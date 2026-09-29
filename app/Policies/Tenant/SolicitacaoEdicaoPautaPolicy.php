<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\SolicitacaoEdicaoPauta;
use App\Models\Tenant\User;

class SolicitacaoEdicaoPautaPolicy
{
    /**
     * Determina se o usuário pode listar pedidos de edição de pauta.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('solicitacao-edicao-pauta.viewAny');
    }

    /**
     * Determina se o usuário pode consultar um pedido de edição de pauta.
     */
    public function view(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return $user->can('solicitacao-edicao-pauta.view', $solicitacaoEdicaoPauta);
    }

    /**
     * Determina se o usuário pode criar um pedido de edição de pauta.
     */
    public function create(User $user): bool
    {
        return $user->can('solicitacao-edicao-pauta.create');
    }

    /**
     * Determina se o usuário pode actualizar um pedido de edição de pauta.
     */
    public function update(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return $user->can('solicitacao-edicao-pauta.update', $solicitacaoEdicaoPauta);
    }

    /**
     * Determina se o usuário pode eliminar um pedido de edição de pauta.
     */
    public function delete(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar um pedido de edição de pauta.
     */
    public function restore(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente um pedido de edição de pauta.
     */
    public function forceDelete(User $user, SolicitacaoEdicaoPauta $solicitacaoEdicaoPauta): bool
    {
        return false;
    }
}
