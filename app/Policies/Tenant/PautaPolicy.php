<?php

namespace App\Policies\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;

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
     * Pode aceder à lista de turmas de um curso tutelado específico?
     *
     * Requer 'pautas.viewAny', ser a instituição que oferece o curso ou
     * o tenant tutor activo, e Professor tem adicionalmente de estar associado
     * ao curso tutelado via curso_tutelado_professor.
     */
    public function viewAnyCurso(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('pautas.viewAny') || $user->instituicao_id === null) {
            return false;
        }

        if (! $this->pertenceAInstituicaoCurso($user, $cursoTutelado)) {
            return false;
        }

        return ! $user->hasRole('Professor')
            || $this->professorAssociadoAoCurso($user, $cursoTutelado);
    }

    /**
     * Determina se o utilizador pode ver a pauta de uma turma específica.
     *
     * Requer 'pautas.view' e pertencer à instituição que oferece o curso ou
     * ao tenant tutor activo.
     * Professor adicionalmente tem de lecionar nessa turma
     * (via turma_disciplina_professor).
     */
    public function view(User $user, Turma $turma): bool
    {
        if (! $user->can('pautas.view') || $user->instituicao_id === null) {
            return false;
        }

        if (! $this->pertenceAInstituicao($user, $turma)
            && ! $this->isTutorDoCurso($user, $turma)) {
            return false;
        }

        return ! $user->hasRole('Professor') || $this->isProfessorDaTurma($user, $turma);
    }

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
