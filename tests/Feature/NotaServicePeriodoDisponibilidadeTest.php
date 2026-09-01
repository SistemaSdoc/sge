<?php

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\PeriodoLancamentoNotas;
use App\Services\Tenant\Core\RegraAcademicaService;
use App\Services\Tenant\NotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeNotaService(): NotaService
{
    app()->instance(
        RegraAcademicaService::class,
        Mockery::mock(RegraAcademicaService::class),
    );

    return app(NotaService::class);
}

test('draft note saving does not unlock the next trimester', function () {
    $tdpId = 'tdp-teste';

    PautaStatus::create([
        'turma_disciplina_professor_id' => $tdpId,
        'periodo' => 1,
        'status' => 'rascunho',
    ]);

    $service = makeNotaService();

    expect($service->periodoPodeSerLancado($tdpId, 1))->toBeTrue();
    expect($service->periodoPodeSerLancado($tdpId, 2))->toBeFalse();
    expect($service->periodosDisponiveis($tdpId)[2])->toBeFalse();
});

test('finalizing the previous trimester unlocks the next one', function () {
    $tdpId = 'tdp-finalizado';

    PautaStatus::create([
        'turma_disciplina_professor_id' => $tdpId,
        'periodo' => 1,
        'status' => 'finalizada',
    ]);

    $service = makeNotaService();

    expect($service->periodoPodeSerLancado($tdpId, 2))->toBeTrue();
    expect($service->periodosDisponiveis($tdpId)[2])->toBeTrue();
});

test('director can still save a finalized pauta', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituicao',
        'email' => 'teste@instituicao.test',
        'telefone' => '+244 900 000 000',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subMonths(2)->startOfMonth(),
        'data_fim' => now()->addMonths(10)->endOfMonth(),
        'activo' => true,
    ]);

    PeriodoLancamentoNotas::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'periodo' => 1,
        'data_inicio' => now()->subDays(7)->startOfDay(),
        'data_limite' => now()->addDays(7)->endOfDay(),
    ]);

    $tdpId = 'tdp-finalizado-director';

    PautaStatus::create([
        'turma_disciplina_professor_id' => $tdpId,
        'periodo' => 1,
        'status' => 'finalizada',
    ]);

    $service = makeNotaService();

    expect(
        $service->podeSalvarOuFinalizar($tdpId, 1, $instituicao->id, true)['pode'],
    )->toBeTrue();

    expect(
        $service->podeSalvarOuFinalizar($tdpId, 1, $instituicao->id, false)['pode'],
    )->toBeFalse();
});
