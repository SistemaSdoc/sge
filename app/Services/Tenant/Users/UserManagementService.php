<?php

namespace App\Services\Tenant\Users;

use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function index(User $actor): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('instituicao_id', $actor->instituicao_id))
            ->orderBy('nome')
            ->orderBy('id')
            ->paginate(15)
            ->through(function (User $user) use ($actor): array {
                return [
                    'id' => $user->getKey(),
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'telefone' => $user->telefone,
                    'avatar' => $user->avatar,
                    'instituicao_id' => $user->instituicao_id,
                    'roles' => $user->getRoleNames()->values()->all(),
                    'directPermissions' => $user->getDirectPermissions()->pluck('name')->values()->all(),
                    'inheritedPermissions' => $user->getPermissionsViaRoles()->pluck('name')->values()->all(),
                    'can' => [
                        'update' => $actor->can('update', $user),
                        'delete' => $actor->can('delete', $user),
                        'manage_permissions' => $actor->can('update', $user),
                    ],
                ];
            });
    }

    /** @return array<int, array{id: int, name: string}> */
    public function roles(?User $actor = null, ?User $target = null): array
    {
        $query = Role::query()
            ->where('guard_name', 'tenant')
            ->whereNotIn('name', ['SuperAdmin', 'Aluno', 'Candidato']);

        if ($actor?->isSubdirector()) {
            $query->whereNotIn('name', ['Director', 'Subdirector']);
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => ['id' => $role->id, 'name' => $role->name])
            ->all();
    }
}
