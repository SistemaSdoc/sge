<?php

namespace App\Policies;

use App\Models\SolicitacaoDocumento;
use App\Models\User;

class SolicitacaoDocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        // Restringe explicitamente a visualização a perfis de gestão/secretaria
        // e administradores — exclui Professores.
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria', 'SuperAdmin']);
    }

    /**
     * Determina se o usuário pode visualizar uma solicitação.
     * O segundo argumento é opcional para evitar erros quando a política for
     * invocada para outros modelos (ex: Turma).
     */
    public function view(User $user, $solicitacao = null): bool
    {
        // Se o segundo argumento não for uma solicitação válida, nega o acesso
        if (! $solicitacao instanceof SolicitacaoDocumento) {
            return false;
        }

        return $user->id === $solicitacao->aluno?->user_id
            || $user->instituicao_id === $solicitacao->instituicao_emissora_id
            || $user->instituicao_id === $solicitacao->instituicao_tutora_id
            || $user->hasRole('SuperAdmin');
    }

    public function marcarComoPago(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function decidir(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id) {
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
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function marcarComoLevantado(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function marcarComoPronto(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->instituicao_id) {
            return false;
        }

        return $user->instituicao_id === $solicitacao->instituicaoResponsavelId();
    }

    public function delete(User $user, SolicitacaoDocumento $solicitacao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Aluno')) {
            if ($solicitacao->aluno && $user->id === $solicitacao->aluno->user_id) {
                return $solicitacao->status === SolicitacaoDocumento::STATUS_ENTREGUE || (bool) $solicitacao->data_levantamento;
            }
            return false;
        }

        if ($user->instituicao_id && $user->hasAnyRole(['Secretaria', 'Director', 'Subdirector'])) {
            return $user->instituicao_id === $solicitacao->instituicaoResponsavelId()
                && ($solicitacao->status === SolicitacaoDocumento::STATUS_ENTREGUE || (bool) $solicitacao->data_levantamento);
        }

        return false;
    }
}