<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Http\Controllers\Controller;
use App\Services\Tenant\Auth\FacebookAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class FacebookAuthController extends Controller
{
    public function __construct(private FacebookAuthService $facebookAuthService) {}

    public function redirect(): RedirectResponse
    {
        return $this->facebookAuthService->handleRedirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $this->facebookAuthService->handleCallback($request);

            return redirect()->intended('/dashboard');

        } catch (UnauthorizedGoogleUserException) {
            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Não foi possível iniciar sessão com esta conta Facebook. Confirme que o seu email já está cadastrado e tente novamente.',
            ]);

        } catch (InvalidStateException $e) {
            Log::warning('[Facebook Auth Controller] InvalidStateException apanhada', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Falha na autenticação com Facebook. Por favor, tente novamente.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Facebook Auth Controller] Exceção inesperada apanhada', [
                'class' => \get_class($e),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.login')->with('toast', [
                'type' => 'error',
                'message' => 'Não foi possível concluir a autenticação com Facebook. Tente novamente.',
            ]);
        }
    }
}
