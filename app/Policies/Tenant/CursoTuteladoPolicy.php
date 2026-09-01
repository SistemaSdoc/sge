<?php

namespace App\Policies\Tenant;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\User;

class CursoTuteladoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('curso-tutelado.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.view') || $user->instituicao_id === null) {
            return false;
        }

        $cursoTutelado->loadMissing('instituicaoCurso');

        // CORRETO: Verifica se é da instituição que oferece o curso
        $instituicaoOferta = $cursoTutelado->instituicaoCurso?->instituicao_id;
        $instituicaoTutora = $cursoTutelado->instituicao_tutora_id;

        // Professor: pode ver se trabalha no curso
        if ($user->hasRole('Professor')) {
            return $cursoTutelado->professores()
                ->where('professor_id', optional($user->professor)->id)
                ->exists();
        }

        // Director/Administrador: pode ver se é de uma das instituições
        return $user->instituicao_id === $instituicaoOferta
            || $user->instituicao_id === $instituicaoTutora;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('curso-tutelado.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * LÓGICA:
     * - Tutora: pode atualizar conteúdo, currículo, docentes
     * - Oferta: pode atualizar aplicação local (turmas, configurações)
     */
    public function update(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.update')) {
            return false;
        }

        $cursoTutelado->loadMissing('instituicaoCurso');

        if ($this->externalTutelaPendingOrClosed($cursoTutelado)) {
            return false;
        }

        $instituicaoOferta = (string) ($cursoTutelado->instituicaoCurso?->instituicao_id ?? '');
        $instituicaoTutora = (string) ($cursoTutelado->instituicao_tutora_id ?? '');
        $instituicaoUser = (string) ($user->instituicao_id ?? '');

        return $instituicaoUser === $instituicaoOferta
            || $instituicaoUser === $instituicaoTutora;
    }

    public function manageProfessores(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.update')) {
            return false;
        }

        if ($this->externalTutelaPendingOrClosed($cursoTutelado)) {
            return false;
        }

        return true;
    }

    public function manageTurmas(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.update')) {
            return false;
        }

        if ($this->externalTutelaPendingOrClosed($cursoTutelado)) {
            return false;
        }

        return true;
    }

    private function externalTutelaPendingOrClosed(CursoTutelado $cursoTutelado): bool
    {
        if (! $cursoTutelado->curso_tutelado_shared_id) {
            return false;
        }

        $status = CursoTuteladoShared::query()
            ->whereKey($cursoTutelado->curso_tutelado_shared_id)
            ->value('status');

        if ($status === null) {
            return false;
        }

        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        return in_array($statusValue, [
            TutelaStatus::PENDENTE->value,
            TutelaStatus::ENCERRADO->value,
        ], true);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * LÓGICA:
     * - Apenas a Tutora pode remover a tutela
     * - A instituição de oferta não pode remover
     */
    public function delete(User $user, CursoTutelado $cursoTutelado): bool
    {
        if (! $user->can('curso-tutelado.delete')) {
            return false;
        }

        // Apenas a tutora pode remover a tutela
        return $user->instituicao_id === $cursoTutelado->instituicao_tutora_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CursoTutelado $cursoTutelado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CursoTutelado $cursoTutelado): bool
    {
        return false;
    }
}
