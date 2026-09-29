<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\User;

class CursoClasseTurnoPolicy
{
    /**
     * Determina se o usuário pode listar os turnos das classes de cursos tutelados.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cursoclasseturno.viewAny');
    }

    /**
     * Determina se o usuário pode consultar um turno associado a uma classe de curso tutelado.
     */
    public function view(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.view')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode associar um turno a uma classe de curso tutelado.
     */
    public function create(User $user): bool
    {
        return $user->can('cursoclasseturno.create') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o usuário pode actualizar o turno de uma classe de curso tutelado.
     */
    public function update(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.update')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode remover o turno de uma classe de curso tutelado.
     */
    public function delete(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return $user->can('cursoclasseturno.delete')
            && $cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode restaurar a associação entre turno e classe de curso tutelado.
     */
    public function restore(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente a associação entre turno e classe de curso tutelado.
     */
    public function forceDelete(User $user, CursoClasseTurno $cursoClasseTurno): bool
    {
        return false;
    }
}
