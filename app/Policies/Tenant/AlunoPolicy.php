<?php

namespace App\Policies\Tenant;

use App\Models\tenant\Aluno;
use App\Models\tenant\User;

class AlunoPolicy
{
    /**
     * Determina se o utilizador pode listar alunos.
     *
     * Requer a permission 'alunos.viewAny' e que o utilizador
     * tenha uma instituição atribuída.
     * A filtragem por turmas (Professor) é feita na query do controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('alunos.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode ver um aluno específico.
     *
     * Três casos permitidos:
     * 1. O próprio aluno — verificação por identidade, sem permission
     * 2. Utilizador com 'alunos.view' da mesma instituição
     * 3. Professor com 'alunos.view' apenas para alunos das suas turmas
     */
    public function view(User $user, Aluno $aluno): bool
    {
        // O próprio aluno pode sempre ver o seu perfil
        if ($user->aluno?->id === $aluno->id) {
            return true;
        }

        if (! $user->can('alunos.view')) {
            return false;
        }

        $mesmaInstituicao = $aluno->user->instituicao_id === $user->instituicao_id;

        if (! $mesmaInstituicao) {
            return false;
        }

        // Professor só vê alunos das suas turmas
        if ($user->hasRole('Professor')) {
            return $user->professor
                ->turmas()
                ->whereHas('alunos', fn ($q) => $q->where('alunos.id', $aluno->id))
                ->exists();
        }

        return true;
    }

    /**
     * Determina se o utilizador pode criar alunos.
     *
     * Requer a permission 'alunos.create' e instituição atribuída.
     */
    public function create(User $user): bool
    {
        return $user->can('alunos.create') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um aluno.
     *
     * O próprio aluno pode editar o seu perfil sem permission.
     * Outros utilizadores requerem 'alunos.update' e mesma instituição.
     */
    public function update(User $user, Aluno $aluno): bool
    {
        return $user->can('alunos.update') && $aluno->user->instituicao_id === $user->instituicao_id;

    }

    /**
     * Determina se o utilizador pode apagar um aluno.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function delete(User $user, Aluno $aluno): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode restaurar um aluno apagado.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Aluno $aluno): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode apagar permanentemente um aluno.
     *
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Aluno $aluno): bool
    {
        return false;
    }
}
