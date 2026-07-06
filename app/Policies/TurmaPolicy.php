<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

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
     * Super vê todas.
     *
     * Director, SubDirector, Secretaria vêem as da sua instituição.
     *
     * Professor vê as que leciona.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'SubDirector', 'Secretaria', 'Professor'])
            && $user->instituicao_id !== null;
    }

    /**
     * Super vê qualquer uma.
     *
     * Director, SubDirector, Secretaria — só da sua instituição.
     *
     * Professor — só onde leciona alguma disciplina.
     */
    public function view(User $user, Turma $turma): bool
    {
        if ($user->hasAnyRole(['Director', 'SubDirector', 'Secretaria'])) {
            return $this->pertenceAInstituicao($user, $turma);
        }

        if ($user->hasRole('Professor')) {
            return $this->isProfessorDaTurma($user, $turma);
        }

        return false;
    }

    /**
     * Super, Director, Sub, Secretaria podem criar.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'SubDirector', 'Secretaria']);
    }

    /**
     * Super, Director, Sub, Secretaria — só da sua instituição.
     */
    public function update(User $user, Turma $turma): bool
    {
        if ($user->hasAnyRole(['Director', 'SubDirector', 'Secretaria'])) {
            return $this->pertenceAInstituicao($user, $turma);
        }

        return false;
    }

    /**
     * Super, Director, Sub, Secretaria — só da sua instituição.
     */
    public function delete(User $user, Turma $turma): bool
    {
        if ($user->hasAnyRole(['Director', 'SubDirector', 'Secretaria'])) {
            return $this->pertenceAInstituicao($user, $turma);
        }

        return false;
    }

    public function restore(User $user, Turma $turma): bool
    {
        return false;
    }

    public function forceDelete(User $user, Turma $turma): bool
    {
        return false;
    }
}
