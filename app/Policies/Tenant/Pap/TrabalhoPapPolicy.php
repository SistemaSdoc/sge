<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class TrabalhoPapPolicy
{
    /**
     * Determina se um elemento pode submeter uma versão do trabalho.
     */
    public function submeterTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return false;
        }

        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Determina se o professor tutor pode aprovar o trabalho.
     */
    public function aprovarTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Determina se o professor tutor pode solicitar correcções ao trabalho.
     */
    public function solicitarCorrecaoTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Determina se a coordenação tutora pode aprovar definitivamente o trabalho.
     */
    public function aprovarTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPelaCoordenacao()
            && $user->can('grupopap.aprovar')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determina se a coordenação tutora pode pedir correcções ao trabalho.
     */
    public function solicitarCorrecaoTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPelaCoordenacao()
            && $user->can('grupopap.aprovar')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode descarregar versões do trabalho.
     */
    public function downloadVersaoTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        if (! $grupoPap->trabalhoPap) {
            return false;
        }

        $ehIntegrante = $grupoPap->elementos()
            ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
            ->exists();

        if ($ehIntegrante) {
            return true;
        }

        if ($grupoPap->professor_tutor_id === $user->professor?->id) {
            return true;
        }

        return $user->can('grupopap.view')
            && (
                $grupoPap->instituicao()?->id === $user->instituicao_id
                || $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
            );
    }
}
