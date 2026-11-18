<?php

namespace App\Observers;

use App\Models\Central\User as CentralUser;
use App\Models\Tenant\User as TenantUser;
use App\Notifications\User\EmailAlteradoNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserEmailAlteradoObserver
{
    public function updated(CentralUser|TenantUser $user): void
    {
        $oldEmail = $user->getOriginal('email');
        $newEmail = $user->email;

        if (! is_string($oldEmail) || $oldEmail === $newEmail) {
            return;
        }

        $passwordPlain = Str::password(12);

        $user->forceFill([
            'password' => Hash::make($passwordPlain),
        ]);
        $user->saveQuietly();

        $user->notify(new EmailAlteradoNotification(
            user: $user,
            passwordPlain: $passwordPlain,
        ));
    }
}
