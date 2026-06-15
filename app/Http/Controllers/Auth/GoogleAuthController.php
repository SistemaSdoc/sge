<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function __construct(private GoogleAuthService $googleAuthService) {}

    public function redirect(): RedirectResponse
    {
        return $this->googleAuthService->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $user = $this->googleAuthService->handleCallback();

            return redirect()->intended($user->roleRedirectPath());
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Falha na autenticação com Google. Por favor, tente novamente.',
                ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('login')
                ->with('toast', [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ]);
        }
    }
}
