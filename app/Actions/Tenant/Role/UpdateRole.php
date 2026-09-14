<?php

namespace App\Actions\Tenant\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRole
{
    /**
     * @param  array{name: string, permissions?: array<int, string>}  $validated
     */
    public function handle(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated): Role {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);

            return $role->refresh()->load('permissions:id,name');
        });
    }
}
