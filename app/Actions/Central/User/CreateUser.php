<?php

namespace App\Actions\Central\User;

use App\Models\Central\User;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Cria um usuario central e sincroniza os seus papeis.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): User
    {
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->syncRoles($roles);

        return $user->load('roles:id,name');
    }
}
