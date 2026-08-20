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

            // USUÁRIOS
            SuperAdminSeeder::class,
        ]);
    }
}
