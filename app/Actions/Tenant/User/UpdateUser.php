<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use App\Services\Tenant\Users\UserProfileService;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(private readonly UserProfileService $userProfileService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated): User {
            $roles = $validated['roles'] ?? [];
            unset($validated['roles']);

            if (blank($validated['password'] ?? null)) {
                unset($validated['password']);
            }

            $user->update($validated);
            $user->syncRoles($roles);
            $this->userProfileService->syncRoleProfiles($user, $roles);

            return $user->refresh()->load('roles:id,name');
        });
    }
}
