<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class AprovacaoTemaPapPolicy
{
    /**
     * Determina se o utilizador pode consultar o histórico do grupo.
     */
    public function viewHistorico(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->view($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode consultar temas que aguardam melhoria.
     */
    public function viewMelhorias(User $user): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.solicitarMelhoria')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se a coordenação tutora pode aprovar a proposta.
     */
    public function aprovar(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.aprovar')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determina se a coordenação tutora pode reprovar a proposta.
     */
    public function reprovar(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.reprovar')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determina se a coordenação tutora pode solicitar melhorias.
     */
    public function solicitarMelhoria(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.solicitarMelhoria')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o professor tutor pode validar o tema.
     */
    public function aprovarComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $grupoPap->podeSerAprovadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Determina se o professor tutor pode solicitar melhoria do tema.
     */
    public function solicitarMelhoriaComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $grupoPap->podeSerAprovadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }
}
