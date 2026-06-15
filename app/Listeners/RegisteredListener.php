<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class RegisteredListener
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $candidatoRole = Role::where('nome', 'Candidato')->first();

        if ($candidatoRole) {
            if (! $user->roles()->where('role_id', $candidatoRole->id)->exists()) {
                $user->roles()->attach($candidatoRole->id, [
                    'id' => (string) Str::uuid(),
                ]);
            }
        }
    }
}
