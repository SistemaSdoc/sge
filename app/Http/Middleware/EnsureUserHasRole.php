<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $roles  Comma-separated list of allowed roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        // Se não está autenticado, redireciona para login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Verifica se o utilizador tem um dos roles permitidos
        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        // Se não tem o role permitido, redireciona para sua rota apropriada
        return redirect()->to($request->user()->roleRedirectPath());
    }
}
