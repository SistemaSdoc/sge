<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

class PautaPolicy
{
    /**
     * Acesso à listagem de cursos/turmas (pautas).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria', 'Professor'])
            && $user->instituicao_id !== null;
    }

    /**
     * Acesso à pauta de uma turma específica.
     */
    public function view(User $user, Turma $turma): bool
    {
        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])) {
            return $this->pertenceAInstituicao($user, $turma);
        }

        if ($user->hasRole('Professor')) {
            return $this->pertenceAInstituicao($user, $turma)
                && $this->isProfessorDaTurma($user, $turma);
        }

        return false;
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
