<?php

use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\Tenant\EnsurePerfilCompleto;
use App\Models\Tenant\User;
use App\Services\Tenant\NotificacaoService;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Middleware\RoleMiddleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

it('renders the notifications page on a direct browser request', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(NotificacaoService::class);
    $service->shouldReceive('pagina')
        ->once()
        ->with($user)
        ->andReturn(new LengthAwarePaginator([], 0, 20));
    $service->shouldReceive('naoLidas')->once()->with($user)->andReturn(0);
    $this->app->instance(NotificacaoService::class, $service);

    $response = $this->withoutMiddleware([
        PreventAccessFromCentralDomains::class,
        InitializeTenancyByDomain::class,
        'auth:tenant',
        EnsureEmailIsVerified::class,
        RoleMiddleware::class,
        CheckTenantStatus::class,
        EnsurePerfilCompleto::class,
    ])
        ->actingAs($user, 'tenant')
        ->get(route('tenant.dashboard.notificacoes.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('tenant/notificacoes/index')
        ->has('notificacoes.data', 0)
        ->where('naoLidas', 0)
    );
});

it('returns notification data from the bell endpoint as JSON', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(NotificacaoService::class);
    $service->shouldReceive('sino')
        ->once()
        ->with($user)
        ->andReturn(new Collection);
    $service->shouldReceive('naoLidas')->once()->with($user)->andReturn(0);
    $this->app->instance(NotificacaoService::class, $service);

    $response = $this->withoutMiddleware([
        PreventAccessFromCentralDomains::class,
        InitializeTenancyByDomain::class,
        'auth:tenant',
        EnsureEmailIsVerified::class,
        RoleMiddleware::class,
        CheckTenantStatus::class,
        EnsurePerfilCompleto::class,
    ])
        ->actingAs($user, 'tenant')
        ->get(route('tenant.dashboard.notificacoes.sino'), [
            'Accept' => 'application/json',
        ]);

    $response->assertJsonPath('notificacoes', [])
        ->assertJsonPath('nao_lidas', 0);
});

it('redirects direct browser requests from the bell endpoint to the notifications page', function () {
    $user = User::factory()->create();

    $response = $this->withoutMiddleware([
        PreventAccessFromCentralDomains::class,
        InitializeTenancyByDomain::class,
        'auth:tenant',
        EnsureEmailIsVerified::class,
        RoleMiddleware::class,
        CheckTenantStatus::class,
        EnsurePerfilCompleto::class,
    ])
        ->actingAs($user, 'tenant')
        ->get(route('tenant.dashboard.notificacoes.sino'));

    $response->assertRedirect(route('tenant.dashboard.notificacoes.index'));
});
