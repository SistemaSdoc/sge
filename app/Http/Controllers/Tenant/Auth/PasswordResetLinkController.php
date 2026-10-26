<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Apresenta o formulário para solicitar o link de redefinição de password.
     */
    public function create(): Response
    {
        return Inertia::render('tenant/auth/forgot-password', [
            'status' => session('status'),
        ]);
    }

    /**
     * Envia o link de redefinição através do broker de passwords do tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Introduza um endereço de email válido.',
        ]);

        $status = Password::broker('tenant_users')->sendResetLink(
            $request->only('email'),
        );

        $message = match ($status) {
            Password::RESET_LINK_SENT => 'Enviámos o link de redefinição da sua palavra-passe para o seu email.',
            Password::INVALID_USER => 'Não encontrámos uma conta com esse endereço de email.',
            Password::RESET_THROTTLED => 'Aguarde um pouco antes de pedir outro link de redefinição.',
            default => 'Não foi possível enviar o link de redefinição. Tente novamente.',
        };

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', $message)
            : back()->withErrors(['email' => $message]);
    }
}
