<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\User;

class ItemPagavelPolicy
{
    /**
     * Determina se o usuário pode listar itens pagáveis da própria instituição.
     *
     * A instituição deve ser um colégio.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('itemspagaveis.viewAny')
            && $user->instituicao_id !== null
            && $user->instituicao?->tipo === 'colegio';
    }

    /**
     * Determina se o usuário pode consultar um item pagável da própria instituição.
     */
    public function view(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.view')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode criar itens pagáveis.
     */
    public function create(User $user): bool
    {
        return $user->can('itemspagaveis.create');
    }

    /**
     * Determina se o usuário pode actualizar um item pagável da própria instituição.
     */
    public function update(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.update')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode eliminar um item pagável da própria instituição.
     */
    public function delete(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.delete')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode restaurar um item pagável.
     */
    public function restore(User $user, ItemPagavel $itemPagavel): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente um item pagável.
     */
    public function forceDelete(User $user, ItemPagavel $itemPagavel): bool
    {
        return false;
    }
}
