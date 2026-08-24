<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Seeder iniciado');

        try {
            $this->call([
                // PAPÉIS E PERMISSÕES
                PermissionSeeder::class,
                RoleSeeder::class,
                RolePermissionSeeder::class,

                // TABELAS BASE
                // InstituicaoSeeder::class,
                AnosLectivosSimulacaoSeeder::class,
                // AnoLectivoSeeder::class,
                // CursoSeeder::class,
                ClasseSeeder::class,
                TurnoSeeder::class,
                DisciplinaSeeder::class,
                NiveisEnsinoSeeder::class,

                // RELACIONAMENTOS ACADÉMICOS
                InstituicaoCursoSeeder::class,
                // CursoTuteladoSeeder::class,
                // CursoClasseSeeder::class,
                // CursoClasseTurnoSeeder::class,

                // TURMAS
                // TurmaSeeder::class,

                // USUÁRIOS
                // InstituicaoUserSeeder::class,
            ]);

            Log::info('Seeder concluído com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro no Seeder: '.$e->getMessage());
            throw $e;
        }

    }
}
