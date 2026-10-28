<?php

use App\Models\Tenant\User;
use App\Services\Tenant\GrupoPap\GrupoPapNavigationService;
use App\Services\Tenant\Menu\SidebarMenuService;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

it('returns no sidebar items for a guest tenant user', function (): void {
    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn(null);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    Gate::shouldReceive('forUser')->never();

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldNotReceive('resolve');

    expect((new SidebarMenuService($navigation))->build())->toBe([]);
});

it('shows an aluno their assigned grupo pap without granting listing access', function (): void {
    $user = Mockery::mock(new User(['instituicao_id' => null]))->makePartial();
    $user->shouldReceive('hasRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('hasAnyRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('isSuperAdmin')->andReturnFalse();

    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn($user);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    $gate = Mockery::mock(GateContract::class);
    $gate->shouldReceive('allows')->andReturnFalse();
    Gate::shouldReceive('forUser')->once()->with($user)->andReturn($gate);

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldReceive('resolve')->once()->with($user)->andReturn([
        'title' => 'Meu Grupo PAP',
        'href' => '/aluno/grupo-pap',
        'visible' => true,
    ]);

    $menu = (new SidebarMenuService($navigation))->build();
    $items = collect($menu)->flatMap(fn (array $group): array => $group['items']);

    expect($items->firstWhere('key', 'grupos-pap'))->toMatchArray([
        'title' => 'Meu Grupo PAP',
        'href' => '/aluno/grupo-pap',
    ]);
});

it('hides student-only menu items from a superadmin even when the account also has the aluno role', function (): void {
    $user = Mockery::mock(new User(['instituicao_id' => null]))->makePartial();
    $user->shouldReceive('hasRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('SuperAdmin', (array) $roles, true)
            || in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('hasAnyRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('isSuperAdmin')->andReturnTrue();

    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn($user);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    $gate = Mockery::mock(GateContract::class);
    $gate->shouldReceive('allows')->andReturnTrue();
    Gate::shouldReceive('forUser')->once()->with($user)->andReturn($gate);

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldReceive('resolve')->once()->with($user)->andReturn([
        'title' => 'Grupos PAP',
        'href' => '/dashboard/grupos-pap',
        'visible' => true,
    ]);

    $menu = (new SidebarMenuService($navigation))->build();
    $keys = collect($menu)->flatMap(fn (array $group): array => $group['items'])
        ->pluck('key');

    expect($keys)
        ->not->toContain('minhas-notas')
        ->not->toContain('grelha-curricular')
        ->not->toContain('horarios');
});

it('shows student-only menu items to an aluno when their gates allow access', function (): void {
    $user = Mockery::mock(new User(['instituicao_id' => null]))->makePartial();
    $user->shouldReceive('hasRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('hasAnyRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Aluno', (array) $roles, true));
    $user->shouldReceive('isSuperAdmin')->andReturnFalse();

    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn($user);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    $gate = Mockery::mock(GateContract::class);
    $gate->shouldReceive('allows')->andReturnTrue();
    Gate::shouldReceive('forUser')->once()->with($user)->andReturn($gate);

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldReceive('resolve')->once()->with($user)->andReturn([
        'title' => 'Meu Grupo PAP',
        'href' => '/aluno/grupo-pap',
        'visible' => true,
    ]);

    $menu = (new SidebarMenuService($navigation))->build();
    $keys = collect($menu)->flatMap(fn (array $group): array => $group['items'])
        ->pluck('key');

    expect($keys)
        ->toContain('minhas-notas')
        ->toContain('grelha-curricular')
        ->toContain('horarios');
});

it('shows the horarios menu item for a professor when access is allowed', function (): void {
    $user = Mockery::mock(new User(['instituicao_id' => null]))->makePartial();
    $user->shouldReceive('hasRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Professor', (array) $roles, true));
    $user->shouldReceive('hasAnyRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Professor', (array) $roles, true));
    $user->shouldReceive('isSuperAdmin')->andReturnFalse();

    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn($user);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    $gate = Mockery::mock(GateContract::class);
    $gate->shouldReceive('allows')->andReturnTrue();
    Gate::shouldReceive('forUser')->once()->with($user)->andReturn($gate);

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldReceive('resolve')->once()->with($user)->andReturn([
        'title' => 'Meu Grupo PAP',
        'href' => '/professor/grupo-pap',
        'visible' => false,
    ]);

    $menu = (new SidebarMenuService($navigation))->build();
    $keys = collect($menu)->flatMap(fn (array $group): array => $group['items'])
        ->pluck('key');

    expect($keys)->toContain('horarios');
});

it('shows the horarios menu item for a director when access is allowed', function (): void {
    $user = Mockery::mock(new User(['instituicao_id' => null]))->makePartial();
    $user->shouldReceive('hasRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Director', (array) $roles, true));
    $user->shouldReceive('hasAnyRole')
        ->withAnyArgs()
        ->andReturnUsing(fn ($roles): bool => in_array('Director', (array) $roles, true));
    $user->shouldReceive('isSuperAdmin')->andReturnFalse();

    $guard = Mockery::mock();
    $guard->shouldReceive('user')->once()->andReturn($user);
    Auth::shouldReceive('guard')->once()->with('tenant')->andReturn($guard);

    $gate = Mockery::mock(GateContract::class);
    $gate->shouldReceive('allows')->andReturnTrue();
    Gate::shouldReceive('forUser')->once()->with($user)->andReturn($gate);

    $navigation = Mockery::mock(GrupoPapNavigationService::class);
    $navigation->shouldReceive('resolve')->once()->with($user)->andReturn([
        'title' => 'Grupos PAP',
        'href' => '/dashboard/grupos-pap',
        'visible' => false,
    ]);

    $menu = (new SidebarMenuService($navigation))->build();
    $keys = collect($menu)->flatMap(fn (array $group): array => $group['items'])
        ->pluck('key');

    expect($keys)->toContain('horarios');
});
