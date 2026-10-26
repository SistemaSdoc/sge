<?php

namespace App\Policies\Tenant;

use App\Enums\TipoQuadroHonra;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;

/** O director gera ambos os tipos; a secretaria gera por curso e o secretário consulta os seus cursos. */
class QuadroHonraPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->instituicao_id === null) {
            return false;
        }

        if ($user->hasAnyRole(['Director', 'Secretaria'])) {
            return true;
        }

        return $user->hasRole('Secretario do Curso')
            && $user->cursosSecretariados()->exists();
    }

    public function view(User $user, QuadroHonra $quadroHonra): bool
    {
        if ($user->instituicao_id === null) {
            return false;
        }

        if ($quadroHonra->tipo === TipoQuadroHonra::GERAL) {
            return $quadroHonra->curso_tutelado_id === null
                && $user->hasRole('Director');
        }

        $cursoTutelado = $quadroHonra->cursoTutelado;

        if (! $cursoTutelado || ! $this->cursoPertenceAInstituicao($cursoTutelado, $user)) {
            return false;
        }

        if ($user->hasAnyRole(['Director', 'Secretaria'])) {
            return true;
        }

        return $user->hasRole('Secretario do Curso')
            && $user->cursosSecretariados()->whereKey($cursoTutelado->getKey())->exists();
    }

    public function generate(User $user, string $tipo, ?string $cursoTuteladoId = null): bool
    {
        if ($user->instituicao_id === null) {
            return false;
        }

        if ($tipo === TipoQuadroHonra::GERAL->value) {
            return $cursoTuteladoId === null && $user->hasRole('Director');
        }

        if ($tipo !== TipoQuadroHonra::CURSO->value || $cursoTuteladoId === null) {
            return false;
        }

        if (! $user->hasAnyRole(['Secretaria', 'Director'])) {
            return false;
        }

        $cursoTutelado = CursoTutelado::query()->find($cursoTuteladoId);

        return $cursoTutelado !== null && $this->cursoPertenceAInstituicao($cursoTutelado, $user);
    }

    public function export(User $user, QuadroHonra $quadroHonra): bool
    {
        return $this->view($user, $quadroHonra);
    }

    private function cursoPertenceAInstituicao(CursoTutelado $cursoTutelado, User $user): bool
    {
        $cursoTutelado->loadMissing('instituicaoCurso');

        return $cursoTutelado->instituicaoCurso?->instituicao_id === $user->instituicao_id;
    }
}
