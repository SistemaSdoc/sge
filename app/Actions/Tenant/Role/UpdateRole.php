<?php

namespace App\Actions\Tenant\Role;

use App\Services\Tenant\RoleManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRole
{
    public function __construct(private readonly RoleManagementService $roleManagementService) {}

    /**
     * @param  array{name: string, permissions?: array<int, string>}  $validated
     */
    public function handle(Role $role, array $validated): Role
    {
        $permissions = $validated['permissions'] ?? [];
        $allowedPermissions = collect($this->roleManagementService->permissions(Auth::guard('tenant')->user()))
            ->pluck('value')
            ->all();

        if (array_diff($permissions, $allowedPermissions) !== []) {
            throw new AuthorizationException('Não pode atribuir uma ou mais permissões seleccionadas.');
        }

        return DB::transaction(function () use ($role, $validated): Role {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);

            return $role->refresh()->load('permissions:id,name');
        });
    }
}
