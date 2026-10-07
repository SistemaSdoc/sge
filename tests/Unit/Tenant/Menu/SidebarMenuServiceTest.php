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
