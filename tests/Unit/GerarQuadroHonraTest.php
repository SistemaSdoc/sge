<?php

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Models\Tenant\User;
use App\Notifications\Aluno\AlunoQuadroHonraNotification;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\ResolverCriteriosQuadroHonra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('does not generate a quadro when there are no eligible students', function () {
    Notification::fake([AlunoQuadroHonraNotification::class]);

    $calcularCandidatos = Mockery::mock(CalcularCandidatosQuadroHonra::class);
    $calcularCandidatos->shouldReceive('calcular')
        ->once()
        ->andReturn(collect());

    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (Closure $callback) => $callback());

    $utilizador = new User;
    $utilizador->forceFill([
        'id' => 'f787153e-b3bf-46ad-bd49-dc682e6d1b70',
        'instituicao_id' => '0fd49a17-2aa4-4420-9a9b-246c20e141a2',
    ]);

    $gerarQuadroHonra = new GerarQuadroHonra(
        $calcularCandidatos,
        new ResolverCriteriosQuadroHonra,
    );

    expect(fn () => $gerarQuadroHonra->handle([
        'tipo' => 'geral',
        'ano_lectivo_id' => '58977671-514d-44bf-bb06-f68e6a97665a',
    ], $utilizador))->toThrow(
        ValidationException::class,
        'Não é possível gerar o Quadro de Honra porque não há alunos elegíveis.',
    );

    Notification::assertNothingSent();
});
