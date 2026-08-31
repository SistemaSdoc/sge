<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugNotFoundRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->status() === 404) {
            Log::error('404 Not Found', [
                'url' => $request->fullUrl(),
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'tenant_id' => tenancy()->tenant?->getTenantKey(),
                'user_id' => auth('tenant')->user()?->getKey(),
            ]);
        }

        return $response;
    }
}
