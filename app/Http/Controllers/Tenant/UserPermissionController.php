<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\User\UpdateUserPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\User\UpdateUserPermissionsRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserPermissionController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
        private readonly UpdateUserPermissions $updateUserPermissions,
    ) {}

    public function create(User $user)
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();
        $user->load('roles:id,name');

        return Inertia::render('tenant/users/permissions', [
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'roles' => $user->getRoleNames()->values()->all(),
                'directPermissions' => $user->getDirectPermissions()->pluck('name')->values()->all(),
                'inheritedPermissions' => $user->getPermissionsViaRoles()->pluck('name')->values()->all(),
                'isSelf' => $user->is($actor),
                'isSubdirector' => $actor?->isSubdirector(),
                'isDirector' => $user->isDirector(),
            ],
            'allPermissions' => $this->roleManagementService->permissions($actor),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions($actor),
            'currentUser' => [
                'id' => $actor?->id,
                'isSubdirector' => $actor?->isSubdirector(),
                'isSuperAdmin' => $actor?->isSuperAdmin(),
            ],
        ]);
    }

    public function update(UpdateUserPermissionsRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        if ($actor?->isSubdirector() && $user->is($actor)) {
            abort(403, 'Não pode alterar as suas próprias permissões.');
        }

        $this->updateUserPermissions->handle(
            $user,
            $request->validated('permissions', [])
        );

        return to_route('tenant.dashboard.users.index')
            ->with('success', "Permissões atualizadas para {$user->nome}.");
    }
}
