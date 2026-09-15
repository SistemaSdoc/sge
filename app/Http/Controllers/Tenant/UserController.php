<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\User\CreateUser;
use App\Actions\Tenant\User\DeleteUser;
use App\Actions\Tenant\User\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\User\StoreUserRequest;
use App\Http\Requests\Tenant\User\UpdateUserRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use App\Services\Tenant\Users\UserManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly RoleManagementService $roleManagementService,
        private readonly CreateUser $createUser,
        private readonly UpdateUser $updateUser,
        private readonly DeleteUser $deleteUser,
    ) {}

    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('viewAny', User::class);

        return Inertia::render('tenant/users/index', [
            'users' => $this->userManagementService->index($user),
            'roles' => $this->userManagementService->roles(),
            'allPermissions' => $this->roleManagementService->permissions(),
            'groupedPermissions' => $this->roleManagementService->groupedPermissions(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        return Inertia::render('tenant/users/create', [
            'roles' => $this->userManagementService->roles($actor),
            'currentUser' => [
                'id' => $actor?->id,
                'isSubdirector' => $actor?->isSubdirector(),
                'isSuperAdmin' => $actor?->isSuperAdmin(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $data = $request->validated();
        $data['instituicao_id'] = $user->instituicao_id;

        $this->createUser->handle($data);

        return to_route('tenant.dashboard.users.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        return Inertia::render('tenant/users/edit', [
            'user' => [
                ...$user->load('roles:id,name')->only('id', 'nome', 'email', 'telefone', 'roles'),
                'isDirector' => $user->isDirector(),
            ],
            'roles' => $this->userManagementService->roles($actor, $user),
            'currentUser' => [
                'id' => $actor?->id,
                'isSubdirector' => $actor?->isSubdirector(),
                'isSuperAdmin' => $actor?->isSuperAdmin(),
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = Auth::guard('tenant')->user();

        if ($actor?->isSubdirector() && $user->is($actor)) {
            abort(403, 'Não pode alterar o seu próprio perfil de funções.');
        }

        $this->updateUser->handle($user, $request->validated());

        return to_route('tenant.dashboard.users.index')->with('success', 'Usuário actualizado com sucesso.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $this->deleteUser->handle($user);

        return to_route('tenant.dashboard.users.index')->with('success', 'Usuário removido com sucesso.');
    }
}
