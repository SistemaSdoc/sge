<?php

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Policies\Tenant\BancaJuriPapPolicy;
use App\Policies\Tenant\ElementoGrupoPapPolicy;
use App\Policies\Tenant\GrupoPapPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

it('does not allow an aluno to access the general grupo pap listing', function (): void {
    $user = Mockery::mock(User::class);

    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnFalse();
    $user->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();

    expect((new GrupoPapPolicy)->viewAny($user))->toBeFalse();
});

it('allows a non-aluno with the permission to access the grupo pap listing', function (): void {
    $user = Mockery::mock(User::class);

    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnFalse();
    $user->shouldReceive('hasRole')->with('Aluno')->andReturnFalse();
    $user->shouldReceive('can')->with('grupopap.viewAny')->andReturnTrue();

    expect((new GrupoPapPolicy)->viewAny($user))->toBeTrue();
});

it('allows a course secretary to open a group from an assigned course', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = new GrupoPap;
    $turma = new Turma;
    $cursoClasseTurno = new CursoClasseTurno;
    $cursoClasse = new CursoClasse;
    $cursosSecretariados = Mockery::mock(BelongsToMany::class);

    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnTrue();
    $user->shouldReceive('can')->with('grupopap.view')->andReturnTrue();
    $user->shouldReceive('cursosSecretariados')->andReturn($cursosSecretariados);
    $cursosSecretariados->shouldReceive('whereKey')->with('course-1')->andReturn($cursosSecretariados);
    $cursosSecretariados->shouldReceive('exists')->andReturnTrue();
    $cursoClasse->curso_tutelado_id = 'course-1';
    $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
    $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);
    $grupoPap->setRelation('turma', $turma);

    expect((new GrupoPapPolicy)->view($user, $grupoPap))->toBeTrue();
});

it('denies a course secretary access to groups outside assigned courses', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = new GrupoPap;
    $turma = new Turma;
    $cursoClasseTurno = new CursoClasseTurno;
    $cursoClasse = new CursoClasse;
    $cursosSecretariados = Mockery::mock(BelongsToMany::class);

    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnTrue();
    $user->shouldReceive('can')->with('grupopap.view')->andReturnTrue();
    $user->shouldReceive('cursosSecretariados')->andReturn($cursosSecretariados);
    $cursosSecretariados->shouldReceive('whereKey')->with('course-2')->andReturn($cursosSecretariados);
    $cursosSecretariados->shouldReceive('exists')->andReturnFalse();
    $cursoClasse->curso_tutelado_id = 'course-2';
    $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
    $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);
    $grupoPap->setRelation('turma', $turma);

    expect((new GrupoPapPolicy)->view($user, $grupoPap))->toBeFalse();
});

it('denies updating a pap theme to a non-member aluno', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = Mockery::mock(GrupoPap::class);

    $user->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();
    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnFalse();
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
    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnFalse();
    $grupoPap->shouldReceive('podeSerReenviado')->andReturnTrue();
    $user->shouldReceive('can')->with('grupopap.corrigirTema')->andReturnTrue();
    $grupoPap->shouldReceive('elementos')->andReturn(
        Mockery::mock()->shouldReceive('whereHas')->andReturn(
            Mockery::mock()->shouldReceive('exists')->andReturnFalse()->getMock()
        )->getMock()
    );

    expect((new GrupoPapPolicy)->reenviarTema($user, $grupoPap))->toBeFalse();
});

it('keeps course secretaries from performing PAP write actions despite other permissions', function (): void {
    $user = Mockery::mock(User::class);
    $grupoPap = Mockery::mock(GrupoPap::class);
    $elemento = Mockery::mock(ElementoGrupoPap::class);
    $banca = Mockery::mock(BancaJuriPap::class);

    $user->shouldReceive('hasRole')->with('Secretario do Curso')->andReturnTrue();
    $user->shouldReceive('can')->andReturnTrue()->byDefault();

    expect((new GrupoPapPolicy)->create($user))->toBeFalse()
        ->and((new GrupoPapPolicy)->update($user, $grupoPap))->toBeFalse()
        ->and((new ElementoGrupoPapPolicy)->create($user))->toBeFalse()
        ->and((new ElementoGrupoPapPolicy)->update($user, $elemento))->toBeFalse()
        ->and((new ElementoGrupoPapPolicy)->atualizarNota($user, $elemento))->toBeFalse()
        ->and((new ElementoGrupoPapPolicy)->delete($user, $elemento))->toBeFalse()
        ->and((new BancaJuriPapPolicy)->create($user, $grupoPap))->toBeFalse()
        ->and((new BancaJuriPapPolicy)->update($user, $banca))->toBeFalse()
        ->and((new BancaJuriPapPolicy)->delete($user, $banca))->toBeFalse();
});
