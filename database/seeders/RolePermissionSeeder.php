<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $mapa = [
            'Director' => [
                // Instituições
                'instituicoes.view',
                'instituicoes.update',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.create',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',
                'turmas.delete',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',
                'professores.delete',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',
                'avisos.delete',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',

                // Classes
                'classes.viewAny',
                'classes.view',
                'classes.create',
                'classes.update',
                'classes.delete',

                // Cursos
                'cursos.viewAny',
                'cursos.view',
                'cursos.create',
                'cursos.update',
                'cursos.delete',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',
                'curso-tutelado.create',
                'curso-tutelado.update',
                'curso-tutelado.delete',

                // Outros
                'utilizadores.gerir',
                'acessos.viewAny',
                'acessos.create',
                'relatorios.view',
                'pagamentos.view',
                'pagamentos.gerir',
            ],

            'Subdirector' => [
                // Instituições
                'instituicoes.view',
                'instituicoes.update',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',
                'turmas.delete',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',

                // Classes
                'classes.viewAny',
                'classes.view',
                'classes.create',
                'classes.update',
                'classes.delete',

                // Cursos
                'cursos.viewAny',
                'cursos.view',
                'cursos.create',
                'cursos.update',
                'cursos.delete',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',
                'curso-tutelado.create',
                'curso-tutelado.update',

                // Outros
                'relatorios.view',
            ],

            'Secretaria' => [
                // Instituições
                'instituicoes.view',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.create',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',

                // Cursos
                'cursos.viewAny',
                'cursos.view',
                'cursos.create',
                'cursos.update',

                // Classes
                'classes.viewAny',
                'classes.view',
                'classes.create',
                'classes.update',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',

                // Outros
                'pagamentos.view',
                'pagamentos.gerir',
            ],

            'Professor' => [
                // Alunos
                'alunos.viewAny',
                'alunos.view',

                // Turmas
                'turmas.viewAny',
                'turmas.view',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
            ],

            'Aluno' => [
                // Avisos
                'avisos.viewAny',
                'avisos.view',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'notas.viewAny',
                'grelha.viewAny',
            ],

            'Candidato' => [],
        ];

        foreach ($mapa as $roleName => $permissions) {
            Role::findByName($roleName)->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
