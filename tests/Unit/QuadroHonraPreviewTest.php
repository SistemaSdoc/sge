<?php

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Http\Controllers\Tenant\QuadroHonraController;
use App\Http\Requests\Tenant\QuadroHonra\GerarQuadroHonraRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\QuadroHonraPdfService;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

uses(TestCase::class);

it('returns only fields rendered in the eligibility preview', function () {
    $user = new User;
    $user->forceFill([
        'id' => 'f787153e-b3bf-46ad-bd49-dc682e6d1b70',
        'instituicao_id' => '0fd49a17-2aa4-4420-9a9b-246c20e141a2',
    ]);

    $request = Mockery::mock(GerarQuadroHonraRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'tipo' => 'geral',
        'ano_lectivo_id' => '58977671-514d-44bf-bb06-f68e6a97665a',
    ]);
    $request->shouldReceive('user')->with('tenant')->once()->andReturn($user);

    $calcularCandidatos = Mockery::mock(CalcularCandidatosQuadroHonra::class);
    $calcularCandidatos->shouldReceive('calcular')
        ->once()
        ->with(
            '58977671-514d-44bf-bb06-f68e6a97665a',
            '0fd49a17-2aa4-4420-9a9b-246c20e141a2',
            null,
            null,
            null,
        )
        ->andReturn(collect([[
            'turma_aluno_id' => 'turma-aluno-id',
            'aluno_id' => 'aluno-id',
            'nome_aluno' => 'Ana Costa',
            'matricula' => 'ALU00001',
            'classe' => '12.ª',
            'curso' => 'Informática',
            'turma' => 'A',
            'media' => 17.5,
            'faltas_total' => 2,
            'notas_abaixo_dez' => 0,
        ]]));

    $controller = new QuadroHonraController(
        Mockery::mock(GerarQuadroHonra::class),
        $calcularCandidatos,
        Mockery::mock(QuadroHonraPdfService::class),
    );

    $response = $controller->previsualizar($request);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getData(true))->toBe([
            'candidatos' => [[
                'turma_aluno_id' => 'turma-aluno-id',
                'nome_aluno' => 'Ana Costa',
                'classe' => '12.ª',
                'curso' => 'Informática',
                'turma' => 'A',
                'media' => 17.5,
            ]],
        ]);
});
