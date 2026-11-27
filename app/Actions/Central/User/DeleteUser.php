<?php

namespace App\Actions\Central\User;

use App\Models\Central\User;

class DeleteUser
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Remove um usuario central.
     */
    public function handle(User $user): void
    {
        $user->delete();
    }
}
