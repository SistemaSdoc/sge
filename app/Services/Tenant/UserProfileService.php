<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;

class UserProfileService
{
    /**
     * @param  array<int, string>|Collection<int, string>  $roles
     */
    public function syncRoleProfiles(User $user, array|Collection $roles): void
    {
        $roleNames = collect($roles)
            ->map(fn ($role) => \is_string($role) ? trim($role) : $role)
            ->filter()
            ->values()
            ->all();

        $hasProfessorRole = \in_array('Professor', $roleNames, true)
            || $user->fresh()->hasRole('Professor');

        if (! $hasProfessorRole) {
            return;
        }

        Professor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'especialidade' => null,
                'nivel_academico' => null,
            ]
        );
    }
}
