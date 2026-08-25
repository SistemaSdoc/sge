<?php

namespace App\Exceptions;

use App\Enums\TenantStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantDatabaseNotExistException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        Log::warning('Tenant database does not exist', [
            'tenant_id' => tenancy()->tenant?->id,
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): SymfonyResponse
    {
        $tenant = tenancy()->tenant;

        if ($tenant?->status === TenantStatus::PENDING) {
            return Inertia::render('errors/tenant-pending-setup')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($tenant?->status === TenantStatus::PROVISIONING) {
            $response = Inertia::render('errors/tenant-configuring', [
                'tenant_name' => $tenant->id,
            ])->toResponse($request);
            $response->setStatusCode(503);
            $response->headers->set('X-Tenant-Status', TenantStatus::PROVISIONING->value);

            return $response;
        }

        if ($tenant?->status === TenantStatus::FAILED) {
            $response = Inertia::render('errors/tenant-failed')->toResponse($request);
            $response->setStatusCode(503);
            $response->headers->set('X-Tenant-Status', TenantStatus::FAILED->value);

            return $response;
        }

        return Inertia::render('errors/503')->toResponse($request)->setStatusCode(503);
    }
}
