<?php

use App\Models\Central\AnoLectivo;
use App\Models\Central\User;
use App\Services\Central\AnoLectivoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function centralAnoLectivoAdmin(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $role = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::create([
        'name' => 'anos-lectivos.manage',
        'guard_name' => 'web',
    ]));
    $user->assignRole($role);

    return $user;
}

test('superadmin consegue gerir anos lectivos no central', function (): void {
    $user = centralAnoLectivoAdmin();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.anos-lectivos.store'), [
            'ano_inicio' => 2035,
        ])
        ->assertRedirect(route('central.dashboard.anos-lectivos.index'));

    $ano = AnoLectivo::query()->where('ano_inicio', 2035)->first();

    expect($ano)->not->toBeNull()
        ->and($ano->nome)->toBe('2035/2036');

    $this->actingAs($user, 'web')
        ->get(route('central.dashboard.anos-lectivos.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('central/anos-lectivos/index')
            ->where('anosLectivos.data.0.nome', '2035/2036'));

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.anos-lectivos.archive', $ano))
        ->assertRedirect(route('central.dashboard.anos-lectivos.index'));

    expect($ano->fresh()->activo)->toBeFalse()
        ->and($ano->fresh()->estado)->toBe('planeado')
        ->and($ano->fresh()->trashed())->toBeTrue();

    $this->actingAs($user, 'web')
        ->post(route('central.dashboard.anos-lectivos.restore', $ano->id))
        ->assertRedirect(route('central.dashboard.anos-lectivos.index'));

    expect($ano->fresh()->trashed())->toBeFalse();
});

test('resolve como actual apenas um ano lectivo activo', function (): void {
    $activo = AnoLectivo::query()->create([
        'ano_inicio' => 2035,
        'data_inicio' => now()->subDays(2),
        'data_fim' => now()->addDays(2),
        'activo' => true,
        'estado' => 'em_curso',
    ]);

    AnoLectivo::query()->create([
        'ano_inicio' => 2036,
        'data_inicio' => now()->subDay(),
        'data_fim' => now()->addDays(3),
        'activo' => false,
        'estado' => 'planeado',
    ]);

    expect(app(AnoLectivoService::class)->current()?->getKey())->toBe($activo->getKey());
});

test('não arquiva o ano lectivo activo', function (): void {
    $ano = AnoLectivo::query()->create([
        'ano_inicio' => 2035,
        'data_inicio' => now()->subDay(),
        'data_fim' => now()->addDay(),
        'activo' => true,
        'estado' => 'em_curso',
    ]);

    expect(fn () => app(AnoLectivoService::class)->arquivar($ano))
        ->toThrow(ValidationException::class);
    expect($ano->fresh()->trashed())->toBeFalse();
});
