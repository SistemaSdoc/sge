<?php

namespace App\Policies\Tenant;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\User;

class AnoLectivoPolicy
{
    /**
     * Determina se o usuário pode listar anos lectivos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']);
    }

    /**
     * Determina se o usuário pode consultar um ano lectivo.
     */
    public function view(User $user, AnoLectivo $anoLectivo): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode criar anos lectivos.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode actualizar um ano lectivo.
     */
    public function update(User $user, AnoLectivo $anoLectivo): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar um ano lectivo.
     */
    public function delete(User $user, AnoLectivo $anoLectivo): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar um ano lectivo.
     */
    public function restore(User $user, AnoLectivo $anoLectivo): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente um ano lectivo.
     */
    public function forceDelete(User $user, AnoLectivo $anoLectivo): bool
    {
        return false;
    }
}
