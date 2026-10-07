<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;

class InstituicaoPolicy
{
    /**
     * Determina se o usuário pode listar todas as instituições.
     *
     * Nenhum role local possui esta permissão. O SuperAdmin é tratado
     * globalmente pelo Gate::before().
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode consultar uma instituição.
     *
     * Requer a permissão 'instituicoes.view' e que o usuário pertença
     * à mesma instituição que está a consultar.
     */
    public function view(User $user, Instituicao $instituicao): bool
    {
        return $user->can('instituicoes.view') && $user->instituicao_id === $instituicao->id;
    }

    /**
     * Determina se o usuário pode criar instituições.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode actualizar uma instituição.
     *
     * Requer a permissão 'instituicoes.update' e que o usuário pertença
     * à mesma instituição que está a editar.
     */
    public function update(User $user, Instituicao $instituicao): bool
    {
        return $user->can('instituicoes.update') && $user->instituicao_id === $instituicao->id;
    }

    /**
     * Determina se o usuário pode eliminar uma instituição.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function delete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar uma instituição.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function restore(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma instituição.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function forceDelete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }
}
