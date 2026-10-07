<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Pagamento;
use App\Models\Tenant\User;

class PagamentoPolicy
{
    /**
     * Determina se o usuário pode listar pagamentos da própria instituição.
     *
     * A instituição deve ser um colégio.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pagamentos.viewAny')
            && $user->instituicao_id !== null
            && $user->instituicao?->tipo === 'colegio';
    }

    /**
     * Determina se o usuário pode consultar um pagamento da própria instituição.
     */
    public function view(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.view')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode criar pagamentos.
     */
    public function create(User $user): bool
    {
        return $user->can('pagamentos.create');
    }

    /**
     * Determina se o usuário pode actualizar um pagamento da própria instituição.
     */
    public function update(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.update')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode eliminar um pagamento da própria instituição.
     */
    public function delete(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.delete')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode restaurar um pagamento.
     */
    public function restore(User $user, Pagamento $pagamento): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente um pagamento.
     */
    public function forceDelete(User $user, Pagamento $pagamento): bool
    {
        return false;
    }
}
