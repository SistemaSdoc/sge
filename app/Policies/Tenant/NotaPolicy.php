<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\User;

class NotaPolicy
{
    /**
     * Determina se o usuário pode listar as próprias notas.
     *
     * Apenas usuários com o perfil de Aluno podem consultar as suas notas.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('notas.viewAny')
            && $user->hasRole('Aluno');
    }

    /**
     * Determina se o usuário pode lançar notas numa disciplina da sua instituição.
     *
     * Director, Subdirector e Secretaria podem lançar notas em qualquer disciplina
     * da instituição. Professor só pode lançar notas na disciplina que lecciona.
     */
    public function create(User $user, ?TurmaDisciplinaProfessor $tdp = null): bool
    {
        if (! $user->can('notas.create') || $user->instituicao_id === null) {
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
     * Determina se o usuário pode actualizar uma nota.
     *
     * Director e Subdirector podem actualizar qualquer nota da sua instituição.
     *
     * Professor só pode actualizar notas da disciplina que lecciona.
     *
     * O bloqueio por período fechado deve ser validado por uma regra própria.
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
     * Determina se o usuário pode exportar a mini-pauta.
     *
     * Director, Subdirector e Secretaria podem exportar qualquer disciplina da
     * sua instituição. Professor só pode exportar a disciplina que lecciona.
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
     * Determina se o usuário pode eliminar uma nota.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function delete(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar uma nota.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function restore(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma nota.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function forceDelete(User $user, Nota $nota): bool
    {
        return false;
    }
}
