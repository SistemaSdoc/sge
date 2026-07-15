<?php

namespace App\Policies;

use App\Models\ItemPagavel;
use App\Models\User;

class ItemPagavelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('itemspagaveis.viewAny');
    }

    public function view(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.view')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    public function create(User $user): bool
    {
        return $user->can('itemspagaveis.create');
    }

    public function update(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.update')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    public function delete(User $user, ItemPagavel $itemPagavel): bool
    {
        return $user->can('itemspagaveis.delete')
            && $itemPagavel->instituicao_id === $user->instituicao_id;
    }

    public function restore(User $user, ItemPagavel $itemPagavel): bool
    {
        return false;
    }

    public function forceDelete(User $user, ItemPagavel $itemPagavel): bool
    {
        return false;
    }
}
