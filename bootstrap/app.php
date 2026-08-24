<?php

use App\Exceptions\TenantDatabaseNotExistException;
use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerificarPropinaEmDia;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'roleOrPermission' => RoleOrPermissionMiddleware::class,
            'propina.em.dia' => VerificarPropinaEmDia::class,
            'tenant.status' => CheckTenantStatus::class,
        ]);

        $middleware->redirectGuestsTo(function () {
            return tenancy()->initialized ? route('tenant.login') : route('central.login');
        });

        $middleware->redirectUsersTo(function () {
            return tenancy()->initialized ? route('tenant.dashboard') : route('central.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TenantDatabaseDoesNotExistException $e, $request) {
            $tenantException = new TenantDatabaseNotExistException($e->getMessage(), $e->getCode(), $e);

            return $tenantException->render($request);
        });
    })->create();
