<?php

namespace App\Policies;

use App\Models\CursoTutelado;
use App\Models\User;

class CursoTuteladoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('curso-tutelado.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.view') || $user->instituicao_id === null) {
            return false;
        }

        if (! $user->hasRole('Professor')) {
            return $user->instituicao_id === $cursoTutelado->instituicao_tutora_id;
        }

        $cursoTutelado->loadMissing('instituicaoCurso');

        $instituicaoId = $cursoTutelado->instituicao_tutora_id
            ?? $cursoTutelado->instituicaoCurso?->instituicao_id;

        if ($instituicaoId !== $user->instituicao_id) {
            return false;
        }

        return $cursoTutelado->professores()
            ->where('professor_id', optional($user->professor)->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('curso-tutelado.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CursoTutelado $cursoTutelado): bool
    {
        return $user->can('curso-tutelado.update') && $user->instituicao_id === $cursoTutelado->instituicao_tutora_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CursoTutelado $cursoTutelado): bool
    {
        return $user->can('curso-tutelado.delete') && $user->instituicao_id === $cursoTutelado->instituicao_tutora_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CursoTutelado $cursoTutelado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CursoTutelado $cursoTutelado): bool
    {
        return false;
    }
}
