<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthService
{
    /**
     * Handle provider callback and return user + token data.
     *
     * @return array{user: User, token: string, ttl: int}
     */
    public function handleProviderCallback(string $provider): array
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->findOrCreateFromSocialite($socialUser);

        $token = auth()->login($user);

        $ttl = auth()->factory()->getTTL();

        return ['user' => $user, 'token' => $token, 'ttl' => $ttl];
    }

    private function findOrCreateFromSocialite(object $socialUser): User
    {
        $user = User::where('facebook_id', $socialUser->getId())->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            if (is_null($user->facebook_id)) {
                $user->update(['facebook_id' => $socialUser->getId()]);
                $user->refresh();
            }

            return $user;
        }

        DB::transaction(function () use ($socialUser, &$user) {
            $user = User::create([
                'nome' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'facebook_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'password' => null,
                'instituicao_id' => null,
            ]);

            $user->refresh();

            $role = Role::where('nome', 'Candidato')->first();

            if ($role) {
                $user->roles()->attach($role->id, ['id' => (string) Str::uuid7()]);
            }
        });

        return $user;
    }
}
