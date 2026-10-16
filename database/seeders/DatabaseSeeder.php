<?php

namespace Database\Seeders;

use Database\Seeders\Central\AnoLectivoSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // AnoLectivoSeeder::class,
            // PAPÉIS E PERMISSÕES
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            DisciplinaSeeder::class,

            // USUÁRIOS
            SuperAdminSeeder::class,
        ]);
    }
}
