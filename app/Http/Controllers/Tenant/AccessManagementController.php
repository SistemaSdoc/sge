<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AccessManagement\StoreRoleAndPermissionRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AccessManagementController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
    ) {}

    /**
     * Lista todos os usuários com suas roles e permissões, além de todas as roles e permissões disponíveis.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('acessos.viewAny');

        $users = User::with([
            'roles:id,name',
            'roles.permissions:id,name',
            'permissions:id,name',
        ])
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('instituicao_id', $user->instituicao_id))
            ->search($request->string('search')->toString())
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'email' => $u->email,
                'avatar' => $u->avatar,
                'roles' => $u->getRoleNames(),
                'directPermissions' => $u->permissions->pluck('name')->values()->all(),
                'inheritedPermissions' => $u->roles
                    ->flatMap(fn ($role) => $role->permissions)
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all(),
            ]);

        return Inertia::render('tenant/gestao-acessos/index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'roles' => Role::where('guard_name', 'tenant')->whereNotIn('name', ['SuperAdmin'])->orderBy('name')->get()->pluck('name'),
            'allPermissions' => $this->roleManagementService->permissions(),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions(),
        ]);
    }

    /**
     * Salva as roles e permissões de um usuário.
     */
    public function store(StoreRoleAndPermissionRequest $request, User $user)
    {
        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        Gate::forUser($actor)->authorize('managePermissions', $user);

        $user->syncRoles($request->validated('roles', []));

        $user->syncPermissions($request->validated('directPermissions', []));

        return back()->with('success', "Roles e permissões atualizados para {$user->nome}.");
    }
}
