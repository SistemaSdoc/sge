<?php

namespace App\Policies\Tenant;

use App\Models\tenant\Professor;
use App\Models\tenant\User;

class ProfessorPolicy
{
    /**
     * Determina se o utilizador pode listar professores.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('professores.viewAny');
    }

    /**
     * Determina se o utilizador pode ver um professor específico.
     *
     * Requer permission e que o professor pertença à mesma instituição.
     * A ligação é via Professor → User → instituicao_id.
     */
    public function view(User $user, Professor $professor): bool
    {
        return $user->can('professores.view')
            && $professor->user->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode criar professores.
     */
    public function create(User $user): bool
    {
        return $user->can('professores.create')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um professor.
     *
     * Requer permission e que o professor pertença à mesma instituição.
     */
    public function update(User $user, Professor $professor): bool
    {
        return $user->can('professores.update')
            && $professor->user->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode apagar um professor.
     *
     * Requer permission e que o professor pertença à mesma instituição.
     */
    public function delete(User $user, Professor $professor): bool
    {
        return $user->can('professores.delete')
            && $professor->user->instituicao_id === $user->instituicao_id;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Professor $professor): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Professor $professor): bool
    {
        return false;
    }
}
