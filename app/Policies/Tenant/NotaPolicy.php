<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\User;

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
    public function create(User $user, ?TurmaDisciplinaProfessor $tdp = null): bool
    {
        if (! $user->can('notas.create') || $user->instituicao_id === null) {
            return false;
        }

        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])) {
            return true; // já garantido pelo instituicao_id acima, se aplicável ao teu modelo
        }

        if ($user->hasRole('Professor')) {
            return $tdp !== null
                && $tdp->professor_id === $user->professor?->id;
        }

        return false;
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
     * Determina se o utilizador pode exportar a mini pauta.
     *
     * Staff (Director, Subdirector, Secretaria) pode exportar qualquer disciplina da sua instituição.
     * Professor só pode exportar a pauta da disciplina que ele próprio lecciona.
     */
    public function export(User $user, ?TurmaDisciplinaProfessor $tdp = null): bool
    {
        if (! $user->can('notas.export') || $user->instituicao_id === null) {
            return false;
        }

        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])) {
            return true;
        }

        if ($user->hasRole('Professor')) {
            return $tdp !== null
                && $tdp->professor_id === $user->professor?->id;
        }

        return false;
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
