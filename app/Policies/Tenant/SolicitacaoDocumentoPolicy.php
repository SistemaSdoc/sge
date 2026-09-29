<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\SolicitacaoDocumento;
use App\Models\Tenant\User;

class SolicitacaoDocumentoPolicy
{
    /** Perfis que podem ver a listagem de solicitações (exclui Professores). */
    private const ROLES_LISTAGEM = ['Director', 'Subdirector', 'Secretaria', 'SuperAdmin'];

    /** Perfis que podem executar acções sobre uma solicitação (igual ao controller). */
    private const ROLES_ACCAO = ['Director', 'Secretaria'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::ROLES_LISTAGEM);
    }

    /**
     * Determina se o utilizador pode visualizar uma solicitação.
     * O segundo argumento é opcional para evitar erros quando a policy for
     * invocada para outros modelos (ex: Turma).
     */
    public function view(User $user, $solicitacao = null): bool
    {
        if (! $solicitacao instanceof SolicitacaoDocumento) {
            return false;
        }

        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->id === $solicitacao->aluno?->user_id) {
            return true;
        }

        if (! $user->instituicao_id) {
            return false;
        }

        return in_array($user->instituicao_id, [
            $solicitacao->instituicao_origem_id,
            $solicitacao->instituicao_emissora_id,
            $solicitacao->instituicao_tutora_id,
        ], true);
    }

    public function marcarComoPago(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        return $this->podeAgirEm($user, $solicitacao->instituicaoResponsavelId());
    }

    public function decidir(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id || ! $user->hasAnyRole(self::ROLES_ACCAO)) {
            return false;
        }

        if ($solicitacao->tipo_documento === 'certificado') {
            return $user->instituicao?->tipo === 'instituto'
                && $user->instituicao_id === $solicitacao->instituicao_tutora_id;
        }

        return $user->instituicao_id === $solicitacao->instituicao_origem_id;
    }

    public function emitir(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        return $this->podeAgirEm($user, $solicitacao->instituicaoResponsavelId());
    }

    public function marcarComoLevantado(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        return $this->podeAgirEm($user, $solicitacao->instituicaoResponsavelId());
    }

    public function marcarComoPronto(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        return $this->podeAgirEm($user, $solicitacao->instituicaoResponsavelId());
    }

    public function delete(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        $entregue = $solicitacao->status === SolicitacaoDocumento::STATUS_ENTREGUE
            || (bool) $solicitacao->data_levantamento;

        if ($user->hasRole('Aluno')) {
            return $solicitacao->aluno
                && $user->id === $solicitacao->aluno->user_id
                && $entregue;
        }

        return $entregue && $this->podeAgirEm($user, $solicitacao->instituicaoResponsavelId());
    }

    /**
     * SuperAdmin, ou Director/Secretaria da instituição indicada.
     */
    private function podeAgirEm(User $user, $instituicaoId): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id || ! $user->hasAnyRole(self::ROLES_ACCAO)) {
            return false;
        }

        return $user->instituicao_id === $instituicaoId;
    }
}
