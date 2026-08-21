<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Curso;
use App\Models\Tenant\User;

class CursoPolicy
{
    /**
     * Determina se o utilizador pode listar cursos.
     *
     * Cursos são genéricos e não pertencem a nenhuma instituição,
     * por isso a verificação é apenas por permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cursos.viewAny');
    }

    /**
     * Determina se o utilizador pode ver um curso específico.
     */
    public function view(User $user, Curso $curso): bool
    {
        return $user->can('cursos.view');
    }

    /**
     * Determina se o utilizador pode criar cursos.
     */
    public function create(User $user): bool
    {
        return $user->can('cursos.create');
    }

    /**
     * Determina se o utilizador pode editar um curso.
     */
    public function update(User $user, Curso $curso): bool
    {
        return $user->can('cursos.update');
    }

    /**
     * Determina se o utilizador pode apagar um curso.
     */
    public function delete(User $user, Curso $curso): bool
    {
        return $user->can('cursos.delete');
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, Curso $curso): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, Curso $curso): bool
    {
        return false;
    }
}
