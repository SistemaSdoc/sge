<?php

namespace App\Policies;

use App\Models\Aluno;
use App\Models\User;

class AlunoPolicy
{
    /**
     * Determina se o utilizador pode listar alunos.
     *
     * Director, Subdirector e Secretaria podem listar os alunos
     * da sua instituição. Professor também pode listar, mas a
     * filtragem pelas suas turmas é feita na query do controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria', 'Professor'])
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode ver um aluno específico.
     *
     * Três casos permitidos:
     * 1. Director, Subdirector ou Secretaria da mesma instituição
     * 2. Professor que tem o aluno numa das suas turmas
     * 3. O próprio aluno a ver o seu perfil
     */
    public function view(User $user, Aluno $aluno): bool
    {
        // O próprio aluno pode ver o seu perfil
        if ($user->aluno?->id === $aluno->id) {
            return true;
        }

        // Verifica se o aluno pertence à mesma instituição do utilizador
        $mesmaInstituicao = $aluno->user->instituicao_id === $user->instituicao_id;

        // Director, Subdirector e Secretaria veem qualquer aluno da instituição
        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']) && $mesmaInstituicao) {
            return true;
        }

        // Professor só vê alunos das suas turmas
        if ($user->hasRole('Professor') && $mesmaInstituicao) {
            return $user->professor
                ->turmas()
                ->whereHas('alunos', fn ($q) => $q->where('alunos.id', $aluno->id))
                ->exists();
        }

        return false;
    }

    /**
     * Determina se o utilizador pode criar alunos.
     *
     * Director, Subdirector e Secretaria podem registar
     * novos alunos na sua instituição.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um aluno.
     *
     * Director, Subdirector e Secretaria podem editar qualquer aluno
     * da sua instituição. O próprio aluno pode editar o seu perfil.
     */
    public function update(User $user, Aluno $aluno): bool
    {
        // O próprio aluno pode editar o seu perfil
        if ($user->aluno?->id === $aluno->id) {
            return true;
        }

        // Director, Subdirector e Secretaria editam alunos da instituição
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])
            && $aluno->user->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode apagar um aluno.
     *
     * Restrito ao SuperAdmin via Gate::before() no AppServiceProvider.
     */
    public function delete(User $user, Aluno $aluno): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode restaurar um aluno apagado.
     *
     * Restrito ao SuperAdmin via Gate::before() no AppServiceProvider.
     */
    public function restore(User $user, Aluno $aluno): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode apagar permanentemente um aluno.
     *
     * Restrito ao SuperAdmin via Gate::before() no AppServiceProvider.
     */
    public function forceDelete(User $user, Aluno $aluno): bool
    {
        return false;
    }
}
