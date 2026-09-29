<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Inscricao;
use App\Models\Tenant\User;

class InscricaoPolicy
{
    /**
     * Determina se o usuário pode listar inscrições.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inscricoes.viewAny');
    }

    /**
     * Determina se o usuário pode consultar uma inscrição da própria instituição.
     */
    public function view(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.view')
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode criar inscrições.
     */
    public function create(User $user): bool
    {
        return $user->can('inscricoes.create');
    }

    /**
     * Determina se o usuário pode actualizar uma inscrição da própria instituição.
     */
    public function update(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.update')
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode eliminar uma inscrição.
     */
    public function delete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar uma inscrição.
     */
    public function restore(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente uma inscrição.
     */
    public function forceDelete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode cancelar uma inscrição activa da própria instituição.
     */
    public function cancelar(User $user, Inscricao $inscricao): bool
    {
        return $user->can('inscricoes.cancelar', $inscricao)
            && $inscricao->status !== 'cancelado'
            && $inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode reactivar uma inscrição cancelada.
     */
    public function reativar(User $user, Inscricao $inscricao): bool
    {
        return $inscricao->status === 'cancelado'
            && $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']);
    }
}
