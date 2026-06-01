<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                'name' => $name,
                'google_id' => $googleId,
                'avatar' => $avatar,
            ]
        );

        Log::info('Google OAuth user authenticated', [
            'user_id' => $user->id,
            'google_id' => $googleId,
            'is_new' => $user->wasRecentlyCreated,
        ]);

        Auth::login($user);

        return $user;
    }
}
