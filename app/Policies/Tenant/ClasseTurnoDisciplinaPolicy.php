<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\User;

class ClasseTurnoDisciplinaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('classeturnodisciplina.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        if (! $user->can('classeturnodisciplina.view')) {
            return false;
        }

        $cursoTutelado = $classeTurnoDisciplina->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado;

        return $cursoTutelado
            && $cursoTutelado->instituicaoCurso?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('classeturnodisciplina.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        $cursoTutelado = $classeTurnoDisciplina->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado;

        if (! $user->can('classeturnodisciplina.update')
            || ! $cursoTutelado
            || $cursoTutelado->curso_tutelado_shared_id === null) {
            return false;
        }

        $status = $cursoTutelado->cursoTuteladoShared?->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        if (in_array($statusValue, ['pendente', 'encerrado'], true)) {
            return false;
        }

        return $cursoTutelado->instituicaoCurso?->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return $this->update($user, $classeTurnoDisciplina)
            && $user->can('classeturnodisciplina.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClasseTurnoDisciplina $classeTurnoDisciplina): bool
    {
        return false;
    }
}
