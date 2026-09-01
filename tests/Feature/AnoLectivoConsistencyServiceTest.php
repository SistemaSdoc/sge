<?php

use App\Models\Tenant\AnoLectivo;
use App\Services\Tenant\AnoLectivoConsistencyService;

it('marca apenas o ano lectivo actual como activo com base nas datas', function () {
    AnoLectivo::create([
        'data_inicio' => now()->subYears(2),
        'data_fim' => now()->subYear()->subDay(),
        'activo' => true,
    ]);

    $expectedActive = AnoLectivo::create([
        'data_inicio' => now()->subDay(),
        'data_fim' => now()->addYear(),
        'activo' => false,
    ]);

    $futureAnoLectivo = AnoLectivo::create([
        'data_inicio' => now()->addYear(),
        'data_fim' => now()->addYears(2),
        'activo' => false,
    ]);

    app(AnoLectivoConsistencyService::class)->sincronizar();

    expect($expectedActive->fresh()->activo)->toBeTrue()
        ->and($futureAnoLectivo->fresh()->activo)->toBeFalse();
});
