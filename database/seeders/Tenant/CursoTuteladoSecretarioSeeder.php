<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CursoTuteladoSecretarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageSecretaries = Permission::findOrCreate('curso.secretarios.manage', 'tenant');
        $viewCourses = [
            Permission::findOrCreate('curso-tutelado.viewAny', 'tenant'),
            Permission::findOrCreate('curso-tutelado.view', 'tenant'),
        ];

        Role::findOrCreate('Secretario do Curso', 'tenant')->syncPermissions($viewCourses);

        foreach (['Coordenador', 'Director'] as $roleName) {
            Role::findOrCreate($roleName, 'tenant')->givePermissionTo($manageSecretaries);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
