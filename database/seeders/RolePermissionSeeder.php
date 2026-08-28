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
            'SuperAdmin' => [
                // Tenants
                'tenants.viewAny',
                'tenants.view',
                'tenants.create',
                'tenants.update',
                'tenants.delete',

                // Users
                'users.viewAny',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
            ],
        ];

        foreach ($mapa as $roleName => $permissions) {
            Role::findByName($roleName)->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
