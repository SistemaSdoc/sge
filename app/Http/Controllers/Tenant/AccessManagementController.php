<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Requests\AccessManagement\StoreRoleAndPermissionRequest;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessManagementController extends Controller
{
    /**
     * Lista todos os usuários com suas roles e permissões, além de todas as roles e permissões disponíveis.
     */
    public function index()
    {
        Gate::authorize('acessos.viewAny');

        $users = User::with('roles', 'permissions')
            ->where('instituicao_id', Auth::user()->instituicaoFiltro())
            ->paginate(10)
            ->through(fn (User $u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'email' => $u->email,
                'avatar' => $u->avatar,
                'roles' => $u->getRoleNames(),
                'directPermissions' => $u->getDirectPermissions()->pluck('name'),
                'inheritedPermissions' => $u->getPermissionsViaRoles()->pluck('name'),
            ]);

        return Inertia::render('gestao-acessos/index', [
            'users' => $users,
            'roles' => Role::whereNotIn('name', ['SuperAdmin'])->get()->pluck('name'),
            'allPermissions' => Permission::all()->pluck('name'),
        ]);
    }

    /**
     * Salva as roles e permissões de um usuário.
     */
    public function store(StoreRoleAndPermissionRequest $request, User $user)
    {
        Gate::authorize('acessos.create');

        $user->syncRoles($request->roles);

        $user->syncPermissions($request->directPermissions);

        return back()->with('success', "Roles e permissões atualizados para {$user->nome}.");
    }
}
