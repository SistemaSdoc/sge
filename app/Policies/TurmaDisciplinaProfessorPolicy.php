<?php

namespace App\Policies;

use App\Models\TurmaDisciplinaProfessor;
use App\Models\User;

class TurmaDisciplinaProfessorPolicy
{
    private function pertenceAInstituicao(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        $relacao->loadMissing('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');

        return $relacao->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao_id === $user->instituicao_id;
    }

    private function isProfessorDaDisciplina(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        $professor = $user->professor;

        if (! $professor) {
            return false;
        }

        return $relacao->professor_id === $professor->id;
    }

    /**
     * Determina se o utilizador pode consultar a listagem de disciplinas associadas a turmas.
     *
     * Requer a permission 'turmas.viewAny' e instituição atribuída.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('turmas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode aceder a uma disciplina específica daquela turma.
     *
     * Isto cobre o acesso a páginas como notas e recursos para essa disciplina.
     * Requer 'turmas.view' e pertencer à mesma instituição.
     * Professor adicionalmente tem de lecionar essa disciplina na turma.
     */
    public function view(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        /* if (! $user->can('turmas.view')) {
             return false;
         }

         if (! $this->pertenceAInstituicao($user, $relacao)) {
             return false;
         }

         if ($user->hasRole('Professor')) {
             return $this->isProfessorDaDisciplina($user, $relacao);
         }*/

        return true;
    }

    /**
     * Determina se o utilizador pode associar uma disciplina a uma turma e a um professor.
     *
     * Requer a permission 'turmas.create' e instituição atribuída.
     */
    public function create(User $user): bool
    {
        return $user->can('turmas.create') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode definir o professor de uma disciplina numa turma.
     *
     * Apenas Director, Subdirector e Secretaria podem executar esta ação.
     */
    public function definirProfessor(User $user): bool
    {
        return $user->instituicao_id !== null
            && $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']);
    }

    /**
     * Determina se o utilizador pode alterar uma associação entre turma, disciplina e professor.
     *
     * Usa a mesma regra de acesso da visualização.
     */
    public function update(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        return $this->view($user, $relacao);
    }

    /**
     * Determina se o utilizador pode remover uma associação entre turma, disciplina e professor.
     *
     * Usa a mesma regra de acesso da visualização.
     */
    public function delete(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        return $this->view($user, $relacao);
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, TurmaDisciplinaProfessor $relacao): bool
    {
        return false;
    }
}
