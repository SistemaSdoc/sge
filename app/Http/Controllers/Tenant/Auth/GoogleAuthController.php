<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Http\Controllers\Controller;
use App\Services\Tenant\Auth\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(private GoogleAuthService $googleAuthService) {}

    public function redirect(): RedirectResponse
    {
        return $this->googleAuthService->handleRedirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $this->googleAuthService->handleCallback($request);

            return redirect()->intended('/dashboard');

        } catch (UnauthorizedGoogleUserException) {
            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Não foi possível iniciar sessão com esta conta Google. Confirme que o seu email já está cadastrado e tente novamente.',
            ]);

        } catch (InvalidStateException $e) {
            Log::warning('[Google Auth Controller] InvalidStateException apanhada', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Falha na autenticação com Google. Por favor, tente novamente.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Google Auth Controller] Exceção inesperada apanhada', [
                'class' => \get_class($e),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Não foi possível concluir a autenticação com Google. Tente novamente.',
            ]);
        }
    }
}
