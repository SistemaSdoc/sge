<?php

namespace App\Services\Tenant\Users;

use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;

class UserProfileService
{
    /**
     * @param  array<int, string|object>|Collection<int, string|object>  $roles
     */
    public function syncRoleProfiles(User $user, array|Collection $roles): void
    {
        $roleNames = collect($roles)
            ->map(fn ($role) => is_string($role) ? trim($role) : ($role->name ?? null))
            ->filter()
            ->values()
            ->all();

        if (! in_array('Professor', $roleNames, true) && ! $user->fresh()->hasRole('Professor')) {
            return;
        }

        Professor::firstOrCreate(
            ['user_id' => $user->getKey()],
            [
                'especialidade' => null,
                'nivel_academico' => null,
            ]
        );
    }
}
