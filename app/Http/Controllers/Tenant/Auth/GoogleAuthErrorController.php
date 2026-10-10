<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Enums\Auth\GoogleAuthError;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Recebe apenas redirects de erro assinados e converte o código numa mensagem flash local ao tenant. */
class GoogleAuthErrorController extends Controller
{
    /**
     * Resolve um código allowlisted, flasha a mensagem na sessão tenant e volta ao login.
     *
     * A rota deve manter o middleware `signed:relative`; a assinatura e a expiração são validadas antes deste método.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $error = GoogleAuthError::tryFrom((string) $request->query('google_error'));

        abort_if($error === null, 400, 'Código de erro Google inválido.');

        return redirect()->route('tenant.login')->with('googleError', $error->message());
    }
}
