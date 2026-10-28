<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use App\Notifications\PasswordAlteradaNotification;
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
        $plainPassword = filled($validated['password'] ?? null)
            ? $validated['password']
            : null;

        $user = DB::transaction(function () use ($user, $validated, $actor): User {
            if ($user->hasRole('Aluno')) {
                unset($validated['roles']);
            }

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
            unset($validated['password_confirmation']);

            $user->update($validated);

            if ($shouldSyncRoles) {
                $user->syncRoles($roles);
                $this->userProfileService->syncRoleProfiles($user, $roles);
            }

            return $user->refresh()->load('roles:id,name');
        });

        if ($plainPassword !== null) {
            $user->notify(new PasswordAlteradaNotification(
                nomeUser: $user->nome,
                email: $user->email,
                alteradoPor: $actor->is($user) ? 'si próprio' : $actor->nome,
                url: route('tenant.login'),
                password: $plainPassword,
            ));
        }

        return $user;
    }
}
