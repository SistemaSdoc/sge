<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class GrupoPapAcessoPolicy
{
    /**
     * Determina se o utilizador pode listar grupos PAP.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Secretario do Curso')
            || (! $user->hasRole('Aluno') && $user->can('grupopap.viewAny'));
    }

    /**
     * Determina se o utilizador pode consultar um grupo PAP.
     */
    public function view(User $user, GrupoPap $grupo): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            if ($grupo->getAttribute('secretaria_course_access') === true) {
                return $user->can('grupopap.view');
            }

            $cursoTuteladoId = $grupo->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;

            return $user->can('grupopap.view')
                && $cursoTuteladoId !== null
                && $user->cursosSecretariados()->whereKey($cursoTuteladoId)->exists();
        }

        if ($user->hasAnyRole(['Director', 'Subdirector'])) {
            return true;
        }

        if ($user->hasRole('Aluno')) {
            return $grupo->alunos()
                ->where('aluno_id', $user->aluno?->id)
                ->exists();
        }

        if ($user->hasRole('Professor')) {
            $professor = $user->professor;

            if (! $professor) {
                return false;
            }

            if ($grupo->professor_tutor_id === $professor->id) {
                return true;
            }

            if ($user->hasAnyRole(['Coordenador do Grupo Disciplinar', 'Membro do Grupo Disciplinar'])) {
                return $user->hasPermissionTo('grupopap.view')
                    && (
                        $grupo->instituicao()?->id === $user->instituicao_id
                        || $grupo->instituicaoTutora()?->id === $user->instituicao_id
                    );
            }

            if ($user->hasPermissionTo('grupopap.view')) {
                $ehCoordenador = CursoTuteladoProfessor::where('professor_id', $professor->id)
                    ->where('coordenador', true)
                    ->exists();

                if ($ehCoordenador) {
                    return $grupo->instituicao()?->id === $user->instituicao_id
                        || $grupo->instituicaoTutora()?->id === $user->instituicao_id;
                }
            }

            return false;
        }

        return $user->hasPermissionTo('grupopap.view')
            && (
                $grupo->instituicao()?->id === $user->instituicao_id
                || $grupo->instituicaoTutora()?->id === $user->instituicao_id
            );
    }

    /**
     * Determina se o utilizador pode criar grupos PAP.
     */
    public function create(User $user): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.create')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um grupo PAP.
     */
    public function update(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $user->hasRole('Professor')) {
            return $user->hasPermissionTo('grupopap.update')
                && $grupoPap->instituicao()?->id === $user->instituicao_id;
        }

        $professor = $user->professor;
        $ehTutor = $grupoPap->professor_tutor_id === $professor?->id;

        return $ehTutor
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode alterar o professor tutor do grupo.
     */
    public function alterarTutor(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.alterarTutor')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode eliminar um grupo PAP.
     */
    public function delete(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.delete')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Impede a restauração directa de grupos PAP.
     */
    public function restore(User $user, GrupoPap $grupoPap): bool
    {
        return false;
    }

    /**
     * Impede a eliminação forçada de grupos PAP.
     */
    public function forceDelete(User $user, GrupoPap $grupoPap): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador pode escolher uma instituição nos filtros PAP.
     */
    public function selecionarInstituicao(User $user): bool
    {
        return $user->can('grupopap.selecionarInstituicao')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode escolher um ano lectivo nos filtros PAP.
     */
    public function selecionarAnoLectivo(User $user): bool
    {
        return $user->can('grupopap.selecionarAnoLectivo')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador tem uma função de direcção PAP.
     */
    public function isPapDirector(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector']);
    }
}
