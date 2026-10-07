<?php

use App\Models\Tenant\User;
use App\Policies\Tenant\UserPolicy;

it('allows managing a user from the same institution', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();
    $target = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $target->instituicao_id = 'institution-a';
    $actor->shouldReceive('can')->with('usuarios.update')->andReturnTrue();
    $actor->shouldReceive('can')->with('usuarios.gerir')->andReturnTrue();
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();
    $actor->shouldReceive('isSubdirector')->andReturnFalse();
    $target->shouldReceive('isDirector')->andReturnFalse();

    expect((new UserPolicy)->update($actor, $target))->toBeTrue();
});

it('denies managing a user from another institution', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();
    $target = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $target->instituicao_id = 'institution-b';
    $actor->shouldReceive('can')->with('usuarios.update')->andReturnTrue();
    $actor->shouldReceive('can')->with('usuarios.gerir')->andReturnTrue();
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();
    $actor->shouldReceive('isSubdirector')->andReturnFalse();
    $target->shouldReceive('isDirector')->andReturnFalse();

    expect((new UserPolicy)->update($actor, $target))->toBeFalse();
});

it('does not allow deleting the authenticated user', function (): void {
    $actor = Mockery::mock(User::class);
    $target = Mockery::mock(User::class);

    $actor->shouldReceive('getKey')->andReturn('user-a');
    $target->shouldReceive('getKey')->andReturn('user-a');
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();
    $actor->shouldReceive('isSubdirector')->andReturnFalse();
    $target->shouldReceive('isDirector')->andReturnFalse();
    $actor->shouldReceive('can')->with('usuarios.delete')->andReturnTrue();

    expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
});

it('allows managing permissions for a same-institution target', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();
    $target = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $target->instituicao_id = 'institution-a';
    $actor->shouldReceive('can')->with('usuarios.gerir')->andReturnTrue();
    $actor->shouldReceive('isSuperAdmin')->andReturnFalse();
    $actor->shouldReceive('isSubdirector')->andReturnFalse();
    $target->shouldReceive('isDirector')->andReturnFalse();

    expect((new UserPolicy)->managePermissions($actor, $target))->toBeTrue();
});

it('does not allow a subdirector to manage their own permissions', function (): void {
    $actor = Mockery::mock(User::class)->makePartial();

    $actor->instituicao_id = 'institution-a';
    $actor->shouldReceive('isDirector')->andReturnFalse();
    $actor->shouldReceive('isSubdirector')->andReturnTrue();
    $actor->shouldReceive('is')->with($actor)->andReturnTrue();

    expect((new UserPolicy)->managePermissions($actor, $actor))->toBeFalse();
});

it('allows a user to view and update only their own profile', function (): void {
    $actor = Mockery::mock(User::class);
    $target = Mockery::mock(User::class);

    $actor->shouldReceive('is')->with($target)->andReturnTrue();

    expect((new UserPolicy)->viewOwnProfile($actor, $target))->toBeTrue()
        ->and((new UserPolicy)->updateOwnProfile($actor, $target))->toBeTrue();
});

it('denies profile access when the target is another user', function (): void {
    $actor = Mockery::mock(User::class);
    $target = Mockery::mock(User::class);

    $actor->shouldReceive('is')->with($target)->andReturnFalse();

    expect((new UserPolicy)->viewOwnProfile($actor, $target))->toBeFalse()
        ->and((new UserPolicy)->updateOwnProfile($actor, $target))->toBeFalse();
});

it('allows academic profile access only for the authenticated student', function (): void {
    $student = Mockery::mock(User::class);
    $otherUser = Mockery::mock(User::class);

    $student->shouldReceive('is')->with($student)->andReturnTrue();
    $student->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();
    $otherUser->shouldReceive('is')->with($student)->andReturnFalse();

    expect((new UserPolicy)->viewAcademicProfile($student, $student))->toBeTrue()
        ->and((new UserPolicy)->viewAcademicProfile($otherUser, $student))->toBeFalse();
});
