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
        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        return redirect()->to($request->user()->roleRedirectPath());
    }
}
