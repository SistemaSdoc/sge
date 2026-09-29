<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Turma;
use App\Models\Tenant\User;

class TurmaPolicy
{
    /**
     * Obtém a instituição responsável pela turma através da sua estrutura curricular.
     */
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

    /**
     * Determina se o usuário pode listar turmas.
     *
     * Requer a permissão 'turmas.viewAny' e uma instituição atribuída.
     * A filtragem das turmas disponíveis deve respeitar o perfil do usuário.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAny(['turmas.viewAny']) && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode consultar uma turma específica.
     *
     * A verificação de pertença institucional e de docência deve ser aplicada
     * quando esta operação for restringida por instituição ou por professor.
     */
    public function view(User $user, Turma $turma): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode criar turmas.
     *
     * Requer a permissão 'turmas.create' e uma instituição atribuída.
     */
    public function create(User $user): bool
    {
        return $user->can('turmas.create') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode actualizar uma turma.
     *
     * Requer a permissão 'turmas.update' e que a turma pertença à sua instituição.
     */
    public function update(User $user, Turma $turma): bool
    {
        if (! $user->can('turmas.update') || ! $this->pertenceAInstituicao($user, $turma)) {
            return false;
        }

        $cursoTutelado = $turma->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado;

        if (! $cursoTutelado || $cursoTutelado->curso_tutelado_shared_id === null) {
            return true;
        }

        $status = $cursoTutelado->cursoTuteladoShared?->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        return ! in_array($statusValue, ['pendente', 'encerrado'], true);
    }

    /**
     * Determina se o usuário pode eliminar uma turma.
     *
     * Requer a permissão 'turmas.delete' e que a turma pertença à sua instituição.
     */
    public function delete(User $user, Turma $turma): bool
    {
        return $this->update($user, $turma) && $user->can('turmas.delete');
    }

    /**
     * Determina se o usuário pode restaurar uma turma.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function restore(User $user, Turma $turma): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma turma.
     *
     * Esta operação permanece exclusiva do SuperAdmin através do Gate::before().
     */
    public function forceDelete(User $user, Turma $turma): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode listar pautas associadas às turmas.
     *
     * Requer a permissão 'pautas.viewAny' e uma instituição atribuída.
     */
    public function viewAnyPauta(User $user): bool
    {
        return $user->can('pautas.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode consultar a pauta de uma turma específica.
     *
     * Requer a permissão 'pautas.view', pertença à mesma instituição e,
     * no caso de Professor, docência na turma.
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
