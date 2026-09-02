<?php

namespace App\Services\Tenant\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FacebookAuthService
{
    public function handleRedirect(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleCallback(Request $request): User
    {
        $socialUser = Socialite::driver('facebook')->user();

        $existingUser = User::where('email', $socialUser->getEmail())->first();

        if ($existingUser === null) {
            throw new UnauthorizedGoogleUserException;
        }

        if (
            $existingUser->facebook_id !== null &&
            $existingUser->facebook_id !== $socialUser->getId()
        ) {
            throw new UnauthorizedGoogleUserException;
        }

        if ($existingUser->facebook_id === null) {
            $existingUser->update([
                'facebook_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        Auth::guard('tenant')->login($existingUser);

        $request->session()->regenerate();

        return $existingUser;
    }
}
