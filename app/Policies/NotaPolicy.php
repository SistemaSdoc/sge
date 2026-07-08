<?php

namespace App\Policies;

use App\Models\Nota;
use App\Models\User;

class NotaPolicy
{
    /**
     * Determina se o utilizador pode listar notas.
     *
     * Apenas o próprio aluno pode ver as suas notas.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('notas.viewAny')
            && $user->hasRole('Aluno');
    }

    /**
     * Determina se o utilizador pode lançar notas.
     *
     * Professor, Director e Subdirector podem lançar notas.
     */
    public function create(User $user): bool
    {
        return $user->can('notas.create')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar uma nota.
     *
     * Director e Subdirector podem editar qualquer nota da sua instituição
     *
     * Professor só pode editar notas que ele próprio lançou,
     *
     * Bloqueio por período fechado será implementado futuramente.
     */
    public function update(User $user, Nota $nota): bool
    {
        if (! $user->can('notas.update')) {
            return false;
        }

        $nota->loadMissing('turmaDisciplinaProfessor.professor.user');

        if ($user->hasAnyRole(['Director', 'Subdirector'])) {
            return $nota->turmaDisciplinaProfessor
                ?->professor
                ?->user
                ?->instituicao_id === $user->instituicao_id;
        }

        return $nota->turmaDisciplinaProfessor?->professor_id === $user->professor?->id;
    }

    /**
     * Determina se o utilizador pode apagar uma nota.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function delete(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Nota $nota): bool
    {
        return false;
    }
}
