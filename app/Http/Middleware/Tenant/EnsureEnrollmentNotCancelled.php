<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrollmentNotCancelled
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user('tenant');

        if (! $user instanceof User || ! $user->temInscricaoCancelada()) {
            return $next($request);
        }

        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A sua inscrição foi cancelada. Contacte a instituição para mais informações.',
            ], Response::HTTP_FORBIDDEN);
        }

        return to_route('tenant.login')->withErrors([
            'email' => 'A sua inscrição foi cancelada. Contacte a instituição para mais informações.',
        ]);
    }
}
