<?php

use App\Models\Tenant\User;
use App\Policies\Tenant\HorarioPolicy;

it('allows students professors and directors to view schedules', function (string $role): void {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('hasAnyRole')
        ->with(['Aluno', 'Professor', 'Director'])
        ->andReturnUsing(fn (array $roles): bool => in_array($role, $roles, true));

    expect((new HorarioPolicy)->viewAny($user))->toBeTrue();
})->with(['Aluno', 'Professor', 'Director']);

it('denies schedule access to users outside the allowed roles', function (): void {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('hasAnyRole')
        ->with(['Aluno', 'Professor', 'Director'])
        ->andReturnFalse();

    expect((new HorarioPolicy)->viewAny($user))->toBeFalse();
});
