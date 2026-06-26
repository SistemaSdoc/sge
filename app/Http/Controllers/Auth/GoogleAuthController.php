<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function __construct(private GoogleAuthService $googleAuthService) {}

    public function redirect(): RedirectResponse
    {
        Log::debug('[Google Auth Controller] redirect() chamado');
        return $this->googleAuthService->redirect();
    }

    public function callback(): RedirectResponse
    {
        Log::debug('[Google Auth Controller] callback() chamado', [
            'url'        => request()->fullUrl(),
            'has_code'   => request()->has('code'),
            'has_state'  => request()->has('state'),
            'has_error'  => request()->has('error'),
            'error'      => request()->get('error'),
            'session_id' => session()->getId(),
        ]);

        try {
            $user = $this->googleAuthService->handleCallback();

            $redirectPath = $user->roleRedirectPath();

            Log::debug('[Google Auth Controller] handleCallback() bem-sucedido', [
                'user_id'       => $user->id,
                'redirect_path' => $redirectPath,
            ]);

            return redirect()->intended($redirectPath);

        } catch (InvalidStateException $e) {
            Log::warning('[Google Auth Controller] InvalidStateException apanhada', [
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->with('toast', [
                    'type'    => 'error',
                    'message' => 'Falha na autenticação com Google. Por favor, tente novamente.',
                ]);

        } catch (\Exception $e) {
            Log::error('[Google Auth Controller] Exceção inesperada apanhada', [
                'class'   => get_class($e),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('login')
                ->with('toast', [
                    'type'    => 'error',
                    'message' => $e->getMessage(),
                ]);
        }
    }
}