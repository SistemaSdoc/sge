<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\UserRequest;
use App\Models\Central\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::query()
            ->with('roles:id,name')
            ->latest()
            ->paginate(10);

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('central/users/index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('central/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->syncRoles($roles);
        $user->load('roles:id,name');

        return redirect()->route('central.dashboard.users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load('roles:id,name');

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('central/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('roles:id,name');

        return Inertia::render('central/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles($roles);
        $user->load('roles:id,name');

        return redirect()->route('central.dashboard.users.index');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('central.dashboard.users.index');
    }
}
