<?php

use App\Exceptions\TenantDatabaseNotExistException;
use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ValidateCrossTenantAccess;
use App\Http\Middleware\VerificarPropinaEmDia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
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
            'cross.tenant' => ValidateCrossTenantAccess::class,
        ]);

        $middleware->redirectGuestsTo(function () {
            return tenancy()->initialized ? route('tenant.login') : route('central.login');
        });

        $middleware->redirectUsersTo(function () {
            return tenancy()->initialized ? route('tenant.dashboard') : route('central.dashboard');
        });

        /**
         * Configura os proxies confiáveis para a aplicação.
         *
         * A instância roda atrás de um AWS Application Load Balancer (ALB) que
         * termina o TLS e encaminha as requisições em HTTP puro via X-Forwarded-*.
         * Sem isto, o Laravel trata cada requisição como http:// e vinda do IP
         * interno do ALB, quebrando geração de URLs (url(), route()), redirects
         * de HTTPS, e o fluxo OAuth/Fortify (redirect_uri_mismatch).
         *
         * `at: '*'` confia em qualquer peer porque os IPs internos do ALB não são
         * fixos nem documentados pela AWS. Isto só é seguro porque o Security Group
         * da instância está restrito a aceitar tráfego apenas do Security Group do
         * ALB (não 0.0.0.0/0) — sem essa restrição, o header X-Forwarded-Proto
         * poderia ser forjado por qualquer requisição direta à instância.
         *
         * @see https://laravel.com/docs/13.x/requests#configuring-trusted-proxies
         */
        $middleware->trustProxies(
            at: '*',
            headers: SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TenantDatabaseDoesNotExistException $e, $request) {
            $tenantException = new TenantDatabaseNotExistException($e->getMessage(), $e->getCode(), $e);

            return $tenantException->render($request);
        });

        $exceptions->render(function (QueryException $e, $request) {
            $isTenantDatabaseNotReady = tenancy()->initialized
                && $e->getConnectionName() === 'tenant'
                && tenancy()->tenant?->status?->value === 'provisioning'
                && in_array($e->getCode(), ['42S02', '42P01'], true);

            if (! $isTenantDatabaseNotReady) {
                return null;
            }

            $tenantException = new TenantDatabaseNotExistException($e->getMessage(), (int) $e->getCode(), $e);

            return $tenantException->render($request);
        });
    })->create();
