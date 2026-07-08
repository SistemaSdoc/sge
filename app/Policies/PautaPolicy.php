<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

class PautaPolicy
{
    /**
     * Determina se o utilizador pode aceder à listagem de pautas.
     *
     * Requer 'pautas.viewAny' e instituição atribuída.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pautas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode ver a pauta de uma turma específica.
     *
     * Requer 'pautas.view' e pertencer à mesma instituição.
     * Professor adicionalmente tem de lecionar nessa turma.
     */
    public function view(User $user, Turma $turma): bool
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
}
