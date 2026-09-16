<?php

namespace App\Actions\Tenant\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateRole
{
    /**
     * @param  array{name: string, permissions?: array<int, string>}  $validated
     */
    public function handle(array $validated): Role
    {
        return DB::transaction(function () use ($validated): Role {
            $permissions = $validated['permissions'] ?? [];
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'tenant']);
            $role->syncPermissions($permissions);

            return $role->load('permissions:id,name');
        });
    }
}
