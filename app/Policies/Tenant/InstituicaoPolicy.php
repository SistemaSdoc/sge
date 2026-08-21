<?php

namespace App\Policies\Tenant;

use App\Models\tenant\Instituicao;
use App\Models\tenant\User;

class InstituicaoPolicy
{
    /**
     * Determina se o utilizador pode listar todas as instituições.
     *
     * Nenhum role local tem esta permissão.
     * SuperAdmin é tratado globalmente pelo Gate::before().
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode ver a instituição.
     *
     * Requer a permission 'instituicoes.view' e que o utilizador
     * pertença à mesma instituição que está a tentar aceder.
     */
public function view(User $user, Instituicao $instituicao): bool
{
    $hasPermission = $user->can('instituicoes.view');
    $sameInstitution = $user->instituicao_id === $instituicao->id;
    
    dd([
        'user' => $user->id,
        'instituicao' => $instituicao->id,
        'hasPermission' => $hasPermission,
        'sameInstitution' => $sameInstitution,
        'result' => $hasPermission && $sameInstitution,
    ]);
    
    return $hasPermission && $sameInstitution;
}

    /**
     * Determina se o utilizador pode criar instituições.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode actualizar a instituição.
     *
     * Requer a permission 'instituicoes.update' e que o utilizador
     * pertença à mesma instituição que está a tentar editar.
     */
    public function update(User $user, Instituicao $instituicao): bool
    {
        return $user->can('instituicoes.update') && $user->instituicao_id === $instituicao->id;
    }

    /**
     * Determina se o utilizador pode apagar a instituição.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function delete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode restaurar a instituição.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Instituicao $instituicao): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode apagar a instituição permanentemente.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Instituicao $instituicao): bool
    {
        return false;
    }
}
