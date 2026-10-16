<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use App\Services\Tenant\Users\UserRoleProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(private readonly UserRoleProfileService $userProfileService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated, User $actor): User
    {
        return DB::transaction(function () use ($user, $validated, $actor): User {
            $shouldSyncRoles = array_key_exists('roles', $validated);
            $roles = $validated['roles'] ?? [];
            unset($validated['roles']);

            if (
                $shouldSyncRoles
                && $actor->is($user)
                && $user->isDirector()
                && ! in_array('Director', $roles, true)
            ) {
                throw new AuthorizationException('Não pode remover o papel Director da própria conta.');
            }

            if (blank($validated['password'] ?? null)) {
                unset($validated['password']);
            }

            $user->update($validated);

            if ($shouldSyncRoles) {
                $user->syncRoles($roles);
                $this->userProfileService->syncRoleProfiles($user, $roles);
            }

            return $user->refresh()->load('roles:id,name');
        });
    }
}
