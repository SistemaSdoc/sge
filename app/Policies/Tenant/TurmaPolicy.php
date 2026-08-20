<?php

namespace App\Policies\Tenant;

use App\Models\tenant\Turma;
use App\Models\tenant\User;

class TurmaPolicy
{
    private function instituicaoId(Turma $turma): ?string
    {
        $turma->loadMissing('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');

        return $turma->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso
            ?->instituicao_id;
    }

    private function pertenceAInstituicao(User $user, Turma $turma): bool
    {
        return $this->instituicaoId($turma) === $user->instituicao_id;
    }

    private function isProfessorDaTurma(User $user, Turma $turma): bool
    {
        $professor = $user->professor;

        if (! $professor) {
            return false;
        }

        return $turma->professores()
            ->where('professor_id', $professor->id)
            ->exists();
    }

    /**
     * Determina se o utilizador pode listar turmas.
     *
     * Requer a permission 'turmas.viewAny' e instituição atribuída.
     * Professor vê apenas as suas turmas — filtragem feita no controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('turmas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode ver uma turma específica.
     *
     * Requer 'turmas.view' e pertencer à mesma instituição.
     * Professor adicionalmente tem de lecionar nessa turma.
     */
    public function view(User $user, Turma $turma): bool
    {
        /*if (! $user->can('turmas.view')) {
            return false;
        }

        if (! $this->pertenceAInstituicao($user, $turma)) {
            return false;
        }

        // Professor tem restrição extra — só vê turmas onde leciona
        if ($user->hasRole('Professor')) {
            return $this->isProfessorDaTurma($user, $turma);
        }*/

        return true;
    }

    /**
     * Determina se o utilizador pode criar turmas.
     *
     * Requer a permission 'turmas.create' e instituição atribuída.
     */
    public function create(User $user): bool
    {
        return $user->can('turmas.create') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode actualizar uma turma.
     *
     * Requer 'turmas.update' e que a turma pertença à sua instituição.
     */
    public function update(User $user, Turma $turma): bool
    {
        return $user->can('turmas.update') && $this->pertenceAInstituicao($user, $turma);
    }

    /**
     * Determina se o utilizador pode apagar uma turma.
     *
     * Requer 'turmas.delete' e que a turma pertença à sua instituição.
     */
    public function delete(User $user, Turma $turma): bool
    {
        return $user->can('turmas.delete') && $this->pertenceAInstituicao($user, $turma);
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Turma $turma): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Turma $turma): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode aceder à listagem de pautas.
     *
     * Requer 'pautas.viewAny' e instituição atribuída.
     */
    public function viewAnyPauta(User $user): bool
    {
        return $user->can('pautas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode ver a pauta de uma turma específica.
     *
     * Requer 'pautas.view' e pertencer à mesma instituição.
     * Professor adicionalmente tem de lecionar nessa turma.
     */
    public function viewPauta(User $user, Turma $turma): bool
    {
        if (! $user->can('pautas.view')) {
            return false;
        }

        if (! $this->pertenceAInstituicao($user, $turma)) {
            return false;
        }

        if ($user->hasRole('Professor')) {
            return $this->isProfessorDaTurma($user, $turma);
        }

        return true;
    }
}
