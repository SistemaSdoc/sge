<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Role\CreateRole;
use App\Actions\Tenant\Role\DeleteRole;
use App\Actions\Tenant\Role\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Role\StoreRoleRequest;
use App\Http\Requests\Tenant\Role\UpdateRoleRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
        private readonly CreateRole $createRole,
        private readonly UpdateRole $updateRole,
        private readonly DeleteRole $deleteRole,
    ) {}

    /**
     * Mostra a lista de funções e permissões disponíveis.
     */
    public function index()
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('viewAny', Role::class);

        return Inertia::render('tenant/roles/index', [
            'roles' => $this->roleManagementService->index(),
            'permissions' => $this->roleManagementService->permissions($actor),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions($actor),
        ]);
    }

    /**
     * Mostra o formulário para criar uma nova função.
     */
    public function create()
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('create', Role::class);

        return Inertia::render('tenant/roles/create', [
            'permissions' => $this->roleManagementService->permissions($actor),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions($actor),
        ]);
    }

    /**
     * Guarda uma nova função no tenant actual.
     */
    public function store(StoreRoleRequest $request)
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('create', Role::class);

        $this->createRole->handle($request->validated());

        return to_route('tenant.dashboard.roles.index')->with('success', 'Role criada com sucesso.');
    }

    /**
     * Mostra o formulário para editar uma função específica.
     */
    public function edit(Role $role)
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('update', $role);

        return Inertia::render('tenant/roles/edit', [
            'role' => $role->load('permissions:id,name,label')->only('id', 'name', 'permissions'),
            'permissions' => $this->roleManagementService->permissions($actor),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions($actor),
        ]);
    }

    /**
     * Actualiza uma função específica.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('update', $role);

        $this->updateRole->handle($role, $request->validated());

        return to_route('tenant.dashboard.roles.index')->with('success', 'Role actualizada com sucesso.');
    }

    /**
     * Remove uma função específica.
     */
    public function destroy(Role $role)
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('delete', $role);

        $this->deleteRole->handle($role);

        return to_route('tenant.dashboard.roles.index')->with('success', 'Role removida com sucesso.');
    }
}
