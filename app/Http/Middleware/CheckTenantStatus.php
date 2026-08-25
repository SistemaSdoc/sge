<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
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
        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenancy()->tenant;

        if (! $tenant) {
            abort(404);
        }

        if ($tenant->status === TenantStatus::PENDING) {
            return Inertia::render('errors/tenant-pending-setup')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($tenant->status === TenantStatus::PROVISIONING) {
            $response = Inertia::render('errors/tenant-configuring', [
                'tenant_name' => $tenant->id,
            ])->toResponse($request);
            $response->setStatusCode(503);
            $response->headers->set('X-Tenant-Status', TenantStatus::PROVISIONING->value);

            return $response;
        }

        if ($tenant->status === TenantStatus::FAILED) {
            $response = Inertia::render('errors/tenant-failed')->toResponse($request);
            $response->setStatusCode(503);
            $response->headers->set('X-Tenant-Status', TenantStatus::FAILED->value);

            return $response;
        }

        if (! $tenant->status->canAccess()) {
            return Inertia::render('tenant/access-denied', [
                'status' => $tenant->status->value,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
