<?php

namespace App\Policies;

use App\Models\Pagamento;
use App\Models\User;

class PagamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pagamentos.viewAny')
            && $user->instituicao_id !== null
            && $user->instituicao?->tipo === 'colegio';
    }

    public function view(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.view')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    public function create(User $user): bool
    {
        return $user->can('pagamentos.create');
    }

    public function update(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.update')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    public function delete(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.delete')
            && $pagamento->instituicao_id === $user->instituicao_id;
    }

    public function restore(User $user, Pagamento $pagamento): bool
    {
        return false;
    }

    public function forceDelete(User $user, Pagamento $pagamento): bool
    {
        return false;
    }
}