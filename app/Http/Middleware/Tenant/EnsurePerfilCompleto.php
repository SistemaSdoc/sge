<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePerfilCompleto
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user('tenant');

        if (! $user instanceof User || ! $user->hasRole('Aluno')) {
            return $next($request);
        }

        $candidato = $user->candidatoDoAluno();

        if ($candidato?->temPerfilCompleto()) {
            return $next($request);
        }

        return to_route('tenant.student-profile.edit')
            ->with('warning', 'Complete os seus dados para continuar.');
    }
}
