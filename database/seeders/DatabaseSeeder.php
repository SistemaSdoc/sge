<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // PAPÉIS E PERMISSÕES
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,

            // TABELAS BASE
            InstituicaoSeeder::class,
            CursoSeeder::class,
            ClasseSeeder::class,
            TurnoSeeder::class,
            DisciplinaSeeder::class,

            // RELACIONAMENTOS ACADÉMICOS
            InstituicaoCursoSeeder::class,
            CursoTuteladoSeeder::class,
            CursoClasseSeeder::class,
            CursoClasseTurnoSeeder::class,

            // TURMAS
            TurmaSeeder::class,

            // USUÁRIOS
            SuperAdminSeeder::class,
            InstituicaoUserSeeder::class,
        ]);
    }
}
