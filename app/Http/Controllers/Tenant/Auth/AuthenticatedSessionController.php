<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\LoginRequest;
use App\Models\Tenant\Instituicao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Stancl\Tenancy\Features\UserImpersonation;

class AuthenticatedSessionController extends Controller
{
    /**
     * Apresenta o formulário de login do tenant.
     *
     * Inclui os dados da instituição e ativa o link de recuperação de password.
     */
    public function create()
    {
        $instituicao = Instituicao::first();

        return Inertia::render('tenant/auth/login', [
            'instituicao' => [
                'nome' => $instituicao?->nome,
                'logo_url' => $instituicao?->logo_url,
            ],
            'canResetPassword' => true,
        ]);
    }

    /**
     * Autentica o utilizador e regenera a sessão atual.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('tenant.dashboard'));
    }

    /**
     * Autentica um utilizador do tenant através de um token de impersonação.
     */
    public function token(string $token)
    {
        return UserImpersonation::makeResponse($token);
    }

    /**
     * Termina a sessão autenticada do tenant e invalida o token CSRF.
     */
    public function destroy(Request $request)
    {
        Auth::guard('tenant')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
