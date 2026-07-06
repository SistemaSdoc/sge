<?php

namespace App\Policies;

use App\Models\GrupoPap;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GrupoPapPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver grupopap');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GrupoPap $grupoPap): bool
    {
        // Se é admin/director/etc — vê tudo
        if ($user->can('editar grupopap') || $user->can('eliminar grupopap')) {
            return $user->can('ver grupopap')
                && $grupoPap->instituicao_id === $user->instituicaoFiltro();
        }

        // Se é aluno — só vê o seu grupo
        if ($user->hasRole('Aluno')) {
            return $grupoPap->alunos()->where('aluno_id', $user->id)->exists();
        }

        return $user->can('ver grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('criar grupopap');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('editar grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }

    /**
     * Determine whether the user can define the defense date for the model.
     */
    public function definirData(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('definir data defesa grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('eliminar grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('editar grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('eliminar grupopap')
            && $grupoPap->instituicao_id === $user->instituicaoFiltro();
    }
}
