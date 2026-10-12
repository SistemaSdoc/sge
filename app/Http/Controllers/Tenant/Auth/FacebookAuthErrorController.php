<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Enums\Auth\FacebookAuthError;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FacebookAuthErrorController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $error = FacebookAuthError::tryFrom((string) $request->query('facebook_error'));

        abort_if($error === null, 400, 'Código de erro Facebook inválido.');

        return redirect()->route('tenant.login')->with('facebookError', $error->message());
    }
}
