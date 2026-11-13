<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class TemaPapPolicy
{
    /**
     * Determina se um elemento do grupo pode corrigir a proposta de tema.
     */
    public function corrigirTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $grupoPap->podeSerEditado() || ! $user->can('grupopap.corrigirTema')) {
            return false;
        }

        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Determina se o utilizador pode actualizar a proposta após uma melhoria.
     */
    public function atualizarTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if ($user->hasRole('Aluno')) {
            return $this->corrigirTema($user, $grupoPap);
        }

        return $user->can('grupopap.update')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode reenviar o tema corrigido.
     */
    public function reenviarTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $grupoPap->podeSerReenviado()) {
            return false;
        }

        if ($user->hasRole('Aluno')) {
            return $user->can('grupopap.corrigirTema')
                && $grupoPap->elementos()
                    ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
                    ->exists();
        }

        return $user->can('grupopap.update')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se um elemento do grupo pode definir a proposta de tema.
     */
    public function definirTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso') || ! $grupoPap->podeDefinirTema()) {
            return false;
        }

        if (! $user->can('grupopap.definirTema')) {
            return false;
        }

        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
