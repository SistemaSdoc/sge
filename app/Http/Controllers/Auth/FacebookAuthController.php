<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\FacebookAuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class FacebookAuthController extends Controller
{
    public function __construct(private FacebookAuthService $facebookAuthService) {}

    public function redirect(): RedirectResponse
    {
        try {
            $redirect = Socialite::driver('facebook')->stateless()->redirect();

            return $redirect;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function callback(): RedirectResponse
    {

        try {
            $result = $this->facebookAuthService->handleProviderCallback('facebook');

            $token = $result['token'];

            $ttlMinutes = $result['ttl'];

            $cookie = cookie('token', $token, $ttlMinutes, '/', null, false, true, false, 'Lax');

            $redirectUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/').'/dashboard';

            return redirect($redirectUrl)->withCookie($cookie);

        } catch (Throwable $e) {
            $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

            return redirect($frontend.'/login?error=oauth_failed');
        }
    }
}
