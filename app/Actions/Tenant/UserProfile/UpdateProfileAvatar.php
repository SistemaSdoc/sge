<?php

namespace App\Actions\Tenant\UserProfile;

use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;

class UpdateProfileAvatar
{
    /** Guarda o novo avatar e actualiza o caminho no usuário. */
    public function handle(User $user, UploadedFile $avatar): void
    {
        $user->update([
            'avatar' => $avatar->store('avatars', 'public'),

        ]);
    }
}
