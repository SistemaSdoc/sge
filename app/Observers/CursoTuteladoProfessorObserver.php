<?php

namespace App\Observers;

use App\Models\CursoTuteladoProfessor;
use Spatie\Permission\PermissionRegistrar;

class CursoTuteladoProfessorObserver
{
    public function created(CursoTuteladoProfessor $pivot)
    {
        if ($pivot->coordenador && $pivot->professor->user) {
            $pivot->professor->user->assignRole('Coordenador');
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function updated(CursoTuteladoProfessor $pivot)
    {
        $user = $pivot->professor->user;

        if (! $user) {
            return;
        }

        // Agora é coordenador
        if ($pivot->coordenador && ! $user->hasRole('Coordenador')) {
            $user->assignRole('Coordenador');
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        // Deixou de ser coordenador
        if (! $pivot->coordenador && $user->hasRole('Coordenador')) {
            // Apenas remove se não é coordenador de nenhum outro curso
            $ehCoordenadorEmOutroCurso = CursoTuteladoProfessor::where('professor_id', $pivot->professor_id)
                ->where('coordenador', true)
                ->where('id', '!=', $pivot->id) // Exclui o atual
                ->exists();

            if (! $ehCoordenadorEmOutroCurso) {
                $user->removeRole('Coordenador');
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }
    }

    public function deleted(CursoTuteladoProfessor $pivot)
    {
        $user = $pivot->professor->user;

        if (! $user || ! $pivot->coordenador) {
            return;
        }

        // Remove role se não é coordenador em mais nenhum curso
        $ehCoordenadorEmOutroCurso = CursoTuteladoProfessor::where('professor_id', $pivot->professor_id)
            ->where('coordenador', true)
            ->exists();

        if (! $ehCoordenadorEmOutroCurso) {
            $user->removeRole('Coordenador');
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
}
