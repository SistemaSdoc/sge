<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Documento;
use App\Models\Tenant\User;

class DocumentoPolicy
{
    /**
     * Determina se o usuário pode listar os documentos da própria instituição.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('documentos.viewAny')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode consultar um documento da própria instituição.
     */
    public function view(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.view')
            && $documento->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode emitir um documento da própria instituição.
     */
    public function emitir(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.emitir')
            && $documento->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode exportar um documento da própria instituição.
     */
    public function exportar(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.exportar')
            && $documento->instituicao_id === $user->instituicao_id;
    }
}
