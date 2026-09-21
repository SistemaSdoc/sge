<?php

namespace App\Policies\Central;

use App\Models\Central\Curso;
use App\Models\Central\User;

class CursoPolicy
{
    /**
     * Determina se o usuario pode consultar algum curso.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode consultar o curso.
     */
    public function view(User $user, Curso $curso): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode criar cursos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode actualizar o curso.
     */
    public function update(User $user, Curso $curso): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode arquivar o curso.
     */
    public function delete(User $user, Curso $curso): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode restaurar o curso arquivado.
     */
    public function restore(User $user, Curso $curso): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    /**
     * Determina se o usuario pode eliminar permanentemente o curso.
     */
    public function forceDelete(User $user, Curso $curso): bool
    {
        return $user->hasRole('SuperAdmin');
    }
}
