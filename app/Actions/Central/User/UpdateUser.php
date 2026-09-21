<?php

namespace App\Actions\Central\User;

use App\Models\Central\User;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Actualiza um usuario central e sincroniza os seus papeis.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): void
    {
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles($roles);
        $user->load('roles:id,name');
    }
}
