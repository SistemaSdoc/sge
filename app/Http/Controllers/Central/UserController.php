<?php

namespace App\Http\Controllers\Central;

use App\Actions\Central\User\CreateUser;
use App\Actions\Central\User\DeleteUser;
use App\Actions\Central\User\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\User\StoreUserRequest;
use App\Http\Requests\Central\User\UpdateUserRequest;
use App\Models\Central\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly CreateUser $createUser,
        private readonly UpdateUser $updateUser,
        private readonly DeleteUser $deleteUser,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::query()
            ->with('roles:id,name')
            ->latest()
            ->paginate(10);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

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
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('central/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->createUser->handle($request->validated());

        return redirect()->route('central.dashboard.users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load('roles:id,name');

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

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
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->updateUser->handle($user, $request->validated());

        return redirect()->route('central.dashboard.users.index');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->deleteUser->handle($user);

        return redirect()->route('central.dashboard.users.index');
    }
}
