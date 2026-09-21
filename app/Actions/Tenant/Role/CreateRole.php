<?php

namespace App\Actions\Tenant\Role;

use App\Services\Tenant\RoleManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateRole
{
    public function __construct(private readonly RoleManagementService $roleManagementService) {}

    /**
     * @param  array{name: string, permissions?: array<int, string>}  $validated
     */
    public function handle(array $validated): Role
    {
        $permissions = $validated['permissions'] ?? [];
        $allowedPermissions = collect($this->roleManagementService->permissions(Auth::guard('tenant')->user()))
            ->pluck('value')
            ->all();

        if (array_diff($permissions, $allowedPermissions) !== []) {
            throw new AuthorizationException('Não pode atribuir uma ou mais permissões seleccionadas.');
        }

        return DB::transaction(function () use ($validated): Role {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'tenant']);
            $role->syncPermissions($validated['permissions'] ?? []);

            return $role->load('permissions:id,name');
        });
    }
}
