<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class PasswordConfirmationGoogleController extends Controller
{
    /**
     * Redirect to Google OAuth provider for password confirmation.
     */
    public function redirect(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the OAuth callback from Google for password confirmation.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            Log::warning('Google OAuth state validation failed during password confirmation', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('password.confirm')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Falha na autenticação com Google. Por favor, tente novamente.',
                ]);
        }

        // Verify that the Google email matches the authenticated user's email
        if ($socialUser->getEmail() !== $request->user()->email) {
            Log::warning('Google OAuth email mismatch during password confirmation', [
                'user_id' => $request->user()->id,
                'expected_email' => $request->user()->email,
                'google_email' => $socialUser->getEmail(),
            ]);

            return redirect()->route('password.confirm')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'O email do Google não corresponde ao da sua conta.',
                ]);
        }

        // Mark the session as password confirmed (same as Fortify does)
        $request->session()->put('auth.password_confirmed_at', Date::now()->unix());

        Log::info('Password confirmed via Google OAuth', [
            'user_id' => $request->user()->id,
        ]);

        // Redirect to the intended URL (stored by RequirePassword middleware)
        return redirect()->intended('/');
    }
}
