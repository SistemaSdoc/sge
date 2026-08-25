<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('documentos.viewAny')
            && $user->instituicao_id !== null;
    }

    public function view(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.view')
            && $documento->instituicao_id === $user->instituicao_id;
    }

    public function emitir(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.emitir')
            && $documento->instituicao_id === $user->instituicao_id;
    }

    public function exportar(User $user, Documento $documento): bool
    {
        return $user->hasPermissionTo('documentos.exportar')
            && $documento->instituicao_id === $user->instituicao_id;
    }
}