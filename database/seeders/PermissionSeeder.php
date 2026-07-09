<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
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
            'notas.viewAny',

            // Grelhar Curricular
            'grelha.viewAny',

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

            // Cursos
            'cursos.viewAny',
            'cursos.view',
            'cursos.create',
            'cursos.update',
            'cursos.delete',

            // Classes
            'classes.viewAny',
            'classes.view',
            'classes.create',
            'classes.update',
            'classes.delete',

            // Cursos Tutelados
            'curso-tutelado.viewAny',
            'curso-tutelado.view',
            'curso-tutelado.create',
            'curso-tutelado.update',
            'curso-tutelado.delete',

            // Sistema
            'utilizadores.gerir',
            'acessos.viewAny',
            'acessos.create',
            'relatorios.view',
            'pagamentos.view',
            'pagamentos.gerir',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
