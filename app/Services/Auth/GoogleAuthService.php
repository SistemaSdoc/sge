<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    /**
     * Redirect to Google OAuth provider.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * @throws InvalidStateException When state validation fails
     */
    public function handleCallback(): User
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            Log::warning('Google OAuth state validation failed', [
                'provider' => 'google',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $googleId = $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName();
        $avatar = $socialUser->getAvatar();

        // Create or update user by email (allows connecting Google to existing accounts)
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'nome' => $name,
                'google_id' => $googleId,
                'avatar' => $avatar,
            ]
        );

        // Se é novo utilizador, atribuir role padrão 'candidato'
        if ($user->wasRecentlyCreated) {
            $candidatoRole = Role::where('nome', 'Candidato')->first();

            if ($candidatoRole && $user->roles()->doesntExist()) {
                $user->roles()->attach($candidatoRole->id, [
                    'id' => (string) Str::uuid7(),
                ]);
            }
        }

        Log::info('Google OAuth user authenticated', [
            'user_id' => $user->id,
            'google_id' => $googleId,
            'is_new' => $user->wasRecentlyCreated,
        ]);

        Auth::login($user);

        return $user;
    }
}
