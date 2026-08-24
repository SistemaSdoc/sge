<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (! $tenant || ! $tenant->status->canAccess()) {
            return Inertia::render('tenant/access-denied', [
                'status' => $tenant?->status->value,
            ])->toResponse($request)->setStatusCode(403);
        }
 
        return $next($request);
    }
}
