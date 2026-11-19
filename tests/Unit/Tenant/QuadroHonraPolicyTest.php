<?php

use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;
use App\Policies\Tenant\QuadroHonraPolicy;

it('allows every institutional profile to view the quadro list and general cards', function (): void {
    $user = new User;
    $user->forceFill(['instituicao_id' => 'institution-id']);
    $quadro = new QuadroHonra;
    $quadro->forceFill([
        'tipo' => 'geral',
    ]);

    $policy = new QuadroHonraPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $quadro))->toBeTrue();
});

it('allows only Director and Secretaria to update quadro criteria', function (bool $hasManagementRole, bool $expected): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->forceFill(['instituicao_id' => 'institution-id']);
    $user->shouldReceive('hasAnyRole')
        ->once()
        ->with(['Director', 'Secretaria'])
        ->andReturn($hasManagementRole);

    expect((new QuadroHonraPolicy)->updateCriteria($user))->toBe($expected);
})->with([
    'director or secretaria' => [true, true],
    'other profile' => [false, false],
]);

it('denies access to the quadro list when the user has no institution', function (): void {
    $user = new User;

    expect((new QuadroHonraPolicy)->viewAny($user))->toBeFalse();
});

it('allows only the director to generate the unified quadro', function (bool $hasInstitution, bool $isDirector, bool $expected): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->forceFill(['instituicao_id' => $hasInstitution ? 'institution-id' : null]);
    $roleCheck = $user->shouldReceive('hasRole')
        ->with('Director')
        ->andReturn($isDirector);

    $hasInstitution ? $roleCheck->once() : $roleCheck->never();

    expect((new QuadroHonraPolicy)->generate($user))->toBe($expected);
})->with([
    'director with institution' => [true, true, true],
    'other role' => [true, false, false],
    'director without institution' => [false, true, false],
]);

it('does not expose legacy course-only boards', function (): void {
    $user = new User;
    $user->forceFill(['instituicao_id' => 'institution-id']);
    $quadro = new QuadroHonra;
    $quadro->forceFill([
        'tipo' => 'curso',
    ]);

    expect((new QuadroHonraPolicy)->view($user, $quadro))->toBeFalse();
});
