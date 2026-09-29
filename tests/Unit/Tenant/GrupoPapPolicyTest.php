<?php

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Policies\Tenant\GrupoPapPolicy;

it('does not allow an aluno to access the general grupo pap listing', function (): void {
    $user = Mockery::mock(User::class);

    $user->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();

    expect((new GrupoPapPolicy)->viewAny($user))->toBeFalse();
});

it('allows a non-aluno with the permission to access the grupo pap listing', function (): void {
    $user = Mockery::mock(User::class);

    $user->shouldReceive('hasRole')->with('Aluno')->andReturnFalse();
    $user->shouldReceive('can')->with('grupopap.viewAny')->andReturnTrue();

    expect((new GrupoPapPolicy)->viewAny($user))->toBeTrue();
});

it('denies updating a pap theme to a non-member aluno', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = Mockery::mock(GrupoPap::class);

    $user->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();
    $grupoPap->shouldReceive('podeSerEditado')->andReturnTrue();
    $user->shouldReceive('can')->with('grupopap.corrigirTema')->andReturnTrue();
    $grupoPap->shouldReceive('elementos')->andReturn(
        Mockery::mock()->shouldReceive('whereHas')->andReturn(
            Mockery::mock()->shouldReceive('exists')->andReturnFalse()->getMock()
        )->getMock()
    );

    expect((new GrupoPapPolicy)->atualizarTema($user, $grupoPap))->toBeFalse();
});

it('denies resending a pap theme when the aluno is not a group member', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = Mockery::mock(GrupoPap::class);

    $user->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();
    $grupoPap->shouldReceive('podeSerReenviado')->andReturnTrue();
    $user->shouldReceive('can')->with('grupopap.corrigirTema')->andReturnTrue();
    $grupoPap->shouldReceive('elementos')->andReturn(
        Mockery::mock()->shouldReceive('whereHas')->andReturn(
            Mockery::mock()->shouldReceive('exists')->andReturnFalse()->getMock()
        )->getMock()
    );

    expect((new GrupoPapPolicy)->reenviarTema($user, $grupoPap))->toBeFalse();
});
