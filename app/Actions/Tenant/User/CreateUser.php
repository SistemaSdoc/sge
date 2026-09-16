<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use App\Notifications\User\UserCriadoNotification;
use App\Services\Tenant\Users\UserProfileService;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function __construct(private readonly UserProfileService $userProfileService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            $roles = $validated['roles'] ?? [];
            $passwordPlain = $validated['password'] ?? null;
            unset($validated['roles']);

            $user = User::create($validated);
            $user->syncRoles($roles);
            $this->userProfileService->syncRoleProfiles($user, $roles);

            if (! blank($passwordPlain)) {
                $user->notify(new UserCriadoNotification($user, (string) $passwordPlain));
            }

            return $user->load('roles:id,name');
        });
    }
}
