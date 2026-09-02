<?php

namespace App\Services\Tenant\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    public function handleRedirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleCallback(Request $request): User
    {
        $user = Socialite::driver('google')->user();

        $existingUser = User::where('email', $user->getEmail())->first();

        if ($existingUser === null) {
            throw new UnauthorizedGoogleUserException;
        }

        if (
            $existingUser->google_id !== null &&
            $existingUser->google_id !== $user->getId()
        ) {
            throw new UnauthorizedGoogleUserException;
        }

        if ($existingUser->google_id === null) {
            $existingUser->update([
                'google_id' => $user->getId(),
                'avatar' => $user->getAvatar(),
            ]);
        }

        Auth::guard('tenant')->login($existingUser);

        $request->session()->regenerate();

        return $existingUser;
    }
}
