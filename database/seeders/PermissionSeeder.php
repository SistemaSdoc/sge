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
            'tenants.viewAny' => 'Ver inquilinos',
            'tenants.view' => 'Ver inquilino',
            'tenants.create' => 'Criar inquilino',
            'tenants.update' => 'Editar inquilino',
            'tenants.delete' => 'Eliminar inquilino',

            'users.viewAny' => 'Ver utilizadores',
            'users.view' => 'Ver utilizador',
            'users.create' => 'Criar utilizador',
            'users.update' => 'Editar utilizador',
            'users.delete' => 'Eliminar utilizador',
        ];

        foreach ($permissions as $permission => $label) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
            );

            Permission::where('name', $permission)
                ->where('guard_name', 'web')
                ->update(['label' => null]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
