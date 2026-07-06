<?php

namespace App\Policies;

use App\Models\Instituicao;
use App\Models\User;

class InstituicaoPolicy
{
    /**
     * Determina se o usuário pode ver todas as instituições.
     *
     * Ninguém a nível local pode listar todas as instituições.
     *
     * Apenas o SuperAdmin tem esse acesso, já garantido globalmente
     * pelo Gate::before() no AppServiceProvider.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode ver a instituição.
     *
     * Director, Subdirector e Secretaria podem ver os dados da
     * instituição, mas apenas da instituição à qual pertencem.
     */
    public function view(User $user, Instituicao $instituicao): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']) && $user->instituicao_id === $instituicao->id;
    }

    /**
     * Determina se o usuário pode criar instituições.
     *
     * Criar uma nova instituição é uma ação exclusiva do SuperAdmin
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode actualizar a instituição.
     *
     * Director e Subdirector podem atualizar os dados da própria
     * instituição.
     */
    public function update(User $user, Instituicao $instituicao): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector']) && $user->instituicao_id === $instituicao->id;
    }

    /**
     * Determina se o usuário pode apagar a instituição.
     *
     * Apagar uma instituição é uma ação destrutiva e sensível,
     * restrita exclusivamente ao SuperAdmin.
     */
    public function delete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar a instituição.
     *
     * Assim como o delete, restaurar uma instituição apagada
     * (soft delete) é restrito ao SuperAdmin.
     */
    public function restore(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode apagar a instituição permanentemente.
     *
     * Force delete remove os dados de forma irreversível,
     * por isso fica travado exclusivamente para o SuperAdmin.
     */
    public function forceDelete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }
}
