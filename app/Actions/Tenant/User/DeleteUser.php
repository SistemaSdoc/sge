<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            foreach ($user->profiles() as $profile) {
                $profile->cleanupOnUserRemoval();
            }

            $user->cursosSecretariados()->detach(); // curso_tutelado_secretario

            $user->syncRoles([]);
            $user->syncPermissions([]);
            $user->delete(); // soft delete
        });
    }
}
