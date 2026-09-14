<?php

use App\Models\Tenant\User;
use App\Policies\Tenant\UserPolicy;

it('allows managing a user from the same institution', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();
    $target = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $target->instituicao_id = 'institution-a';
    $actor->shouldReceive('can')->with('utilizadores.gerir')->andReturnTrue();
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();

    expect((new UserPolicy)->update($actor, $target))->toBeTrue();
});

it('denies managing a user from another institution', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();
    $target = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $target->instituicao_id = 'institution-b';
    $actor->shouldReceive('can')->with('utilizadores.gerir')->andReturnTrue();
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();

    expect((new UserPolicy)->update($actor, $target))->toBeFalse();
});

it('does not allow deleting the authenticated user', function (): void {
    $actor = Mockery::mock(User::class);
    $target = Mockery::mock(User::class);

    $actor->shouldReceive('getKey')->andReturn('user-a');
    $target->shouldReceive('getKey')->andReturn('user-a');

    expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
});
