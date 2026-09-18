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

        /** @var User $currentUser */
        $currentUser = Auth::guard('tenant')->user();
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
                'isSelf' => $currentUser->is($user),
                'isSubdirector' => $user?->isSubdirector(),
                'isDirector' => $user->isDirector(),
            ],
            'allPermissions' => $this->roleManagementService->permissions($user),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions($user),
            'currentUser' => [
                'id' => $currentUser?->id,
                'isSubdirector' => $currentUser?->isSubdirector(),
                'isSuperAdmin' => $currentUser?->isSuperAdmin(),
            ],
        ]);
    }

    public function update(UpdateUserPermissionsRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        /** @var User $currentUser */
        $currentUser = Auth::guard('tenant')->user();

        if ($currentUser?->isSubdirector() && $currentUser->is($user)) {
            abort(403, 'Não pode alterar as suas próprias permissões.');
        }

        $this->updateUserPermissions->handle(
            $user,
            $request->validated('permissions', [])
        );

        return to_route('tenant.dashboard.users.index')
            ->with('success', "Permissões actualizadas para {$user->nome}.");
    }
}
