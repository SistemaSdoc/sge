<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Director',
            'Subdirector',
            'Secretaria',
            'Coordenador',
            'Professor',
            'Aluno',
            'Candidato',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role],
                ['guard_name' => 'tenant']
            );
        }
    }
}
