<?php

namespace App\Policies;

use App\Models\Pagamento;
use App\Models\User;

class PagamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pagamentos.viewAny');
    }

    public function view(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.view')
            && $pagamento->propina->aluno?->instituicao_id === $user->instituicao_id;
    }

    public function create(User $user): bool
    {
        return $user->can('pagamentos.create');
    }

    public function update(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.update')
            && $pagamento->propina->aluno?->instituicao_id === $user->instituicao_id;
    }

    public function delete(User $user, Pagamento $pagamento): bool
    {
        return $user->can('pagamentos.delete')
            && $pagamento->propina->aluno?->instituicao_id === $user->instituicao_id;
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
