<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisteredListener
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        if (! $user->hasRole('Candidato')) {
            $user->assignRole('Candidato');
        }
    }
}
