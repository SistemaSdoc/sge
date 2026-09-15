<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->syncRoles([]);
            $user->syncPermissions([]);
            $user->delete();
        });
    }
}
