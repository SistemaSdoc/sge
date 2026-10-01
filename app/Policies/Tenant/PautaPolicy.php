<?php

namespace App\Policies\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;

class PautaPolicy
{
    /**
     * Determina se o usuário pode listar pautas.
     *
     * Requer a permissão 'pautas.viewAny' e uma instituição atribuída.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pautas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode listar as turmas de um curso tutelado.
     *
     * Requer a permissão 'pautas.viewAny', pertencer à instituição que oferece
     * o curso ou ser o tenant tutor activo. Professor também deve estar
     * associado ao curso tutelado através de curso_tutelado_professor.
     */
    public function viewAnyCurso(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('pautas.viewAny') || $user->instituicao_id === null) {
            return false;
        }

        if ($this->isCoordenadorDoCurso($user, $cursoTutelado)) {
            return true;
        }

        if (! $this->pertenceAInstituicaoCurso($user, $cursoTutelado)) {
            return false;
        }

        return ! $user->hasRole('Professor')
            || $this->professorAssociadoAoCurso($user, $cursoTutelado);
    }

    /**
     * Determina se o usuário pode consultar a pauta de uma turma específica.
     *
     * Requer a permissão 'pautas.view' e pertencer à instituição que oferece
     * o curso ou ao tenant tutor activo. Professor também deve leccionar
     * nessa turma através de turma_disciplina_professor.
     */
    public function view(User $user, Turma $turma): bool
    {
        if (! $user->can('pautas.view') || $user->instituicao_id === null) {
            return false;
        }

        if ($this->isCoordenadorDoCurso($user, $turma)) {
            return true;
        }

        if (! $this->pertenceAInstituicao($user, $turma)
            && ! $this->isTutorDoCurso($user, $turma)) {
            return false;
        }

        return ! $user->hasRole('Professor') || $this->isProfessorDaTurma($user, $turma);
    }

    /**
     * Determina se o curso tutelado pertence à instituição do usuário,
     * à instituição tutora ou a um tenant tutor activo.
     */
    private function pertenceAInstituicaoCurso(User $user, CursoTutelado $cursoTutelado): bool
    {
        $cursoTutelado->loadMissing('instituicaoCurso');

        if ($cursoTutelado->instituicaoCurso?->instituicao_id === $user->instituicao_id) {
            return true;
        }

        if ($cursoTutelado->instituicao_tutora_id === $user->instituicao_id) {
            return true;
        }

        return $this->isTutorDoCurso($user, $cursoTutelado);
    }

    /**
     * Determina se o usuário é tutor activo do curso tutelado.
     */
    private function isTutorDoCurso(User $user, CursoTutelado|Turma $resource): bool
    {
        $cursoTutelado = $resource instanceof Turma
            ? $resource->cursoClasseTurno?->cursoClasse?->cursoTutelado
            : $resource;

        $sharedId = $cursoTutelado?->curso_tutelado_shared_id;

        return $sharedId !== null
            && CursoTuteladoShared::query()
                ->whereKey($sharedId)
                ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
                ->where('status', 'activo')
                ->exists();
    }

    /**
     * Determina se o professor autenticado está associado ao curso tutelado.
     */
    private function professorAssociadoAoCurso(User $user, CursoTutelado $cursoTutelado): bool
    {
        $professor = $user->professor;

        if (! $professor) {
            return false;
        }

        return $cursoTutelado->professores()
            ->where('professor_id', $professor->id)
            ->exists();
    }

    private function isCoordenadorDoCurso(User $user, CursoTutelado|Turma $resource): bool
    {
        $cursoTutelado = $resource instanceof Turma
            ? $resource->cursoClasseTurno?->cursoClasse?->cursoTutelado
            : $resource;
        $professorId = $user->professor?->id;

        return $professorId !== null
            && $cursoTutelado?->professores()
                ->where('professor_id', $professorId)
                ->wherePivot('coordenador', true)
                ->exists();
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

    /**
     * Determina se a turma pertence à instituição do usuário.
     */
    private function pertenceAInstituicao(User $user, Turma $turma): bool
    {
        return $this->instituicaoId($turma) === $user->instituicao_id;
    }

    /**
     * Determina se o professor autenticado lecciona na turma.
     */
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
