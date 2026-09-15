<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;

class UpdateUserPermissions
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function handle(User $user, array $permissions): User
    {
        $user->syncPermissions($permissions);

        return $user->refresh();
    }
}
