<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

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
    public function render(Request $request): Response
    {
        $tenant = tenancy()->tenant;

        if ($tenant && $tenant->status?->value === 'pending') {
            return Inertia::render('errors/tenant-pending-setup');
        }

        return Inertia::render('errors/503');
    }
}
