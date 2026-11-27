<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\User;

class CursoClassePolicy
{
    /**
     * Determina se o usuário pode listar as classes disponíveis num curso tutelado.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cursoclasse.viewAny');
    }

    /**
     * Determina se o usuário pode consultar uma classe associada a um curso tutelado.
     */
    public function view(User $user, CursoClasse $cursoClasse): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return $user->can('cursoclasse.view')
                && $user->cursosSecretariados()
                    ->whereKey($cursoClasse->curso_tutelado_id)
                    ->exists();
        }

        return $user->can('cursoclasse.view')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode associar uma classe a um curso tutelado.
     */
    public function create(User $user): bool
    {
        return $user->can('cursoclasse.create');
    }

    /**
     * Determina se o usuário pode actualizar a classe de um curso tutelado.
     */
    public function update(User $user, CursoClasse $cursoClasse): bool
    {
        return $user->can('cursoclasse.update')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode remover uma classe de um curso tutelado.
     */
    public function delete(User $user, CursoClasse $cursoClasse): bool
    {
        return $user->can('cursoclasse.delete')
            && $cursoClasse->cursoTutelado->instituicaoCurso->instituicao_id === $user->instituicao_id;
    }

    /**
     * Determina se o usuário pode restaurar a associação entre classe e curso tutelado.
     */
    public function restore(User $user, CursoClasse $cursoClasse): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode eliminar permanentemente a associação entre classe e curso tutelado.
     */
    public function forceDelete(User $user, CursoClasse $cursoClasse): bool
    {
        return false;
    }
}
