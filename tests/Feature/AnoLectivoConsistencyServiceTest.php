<?php

use App\Models\Central\AnoLectivo;
use App\Services\Central\AnoLectivoService;
use App\Services\Tenant\AnoLectivoConsistencyService;

it('resolve o ano lectivo default exclusivamente na central', function () {
    $anoCentral = AnoLectivo::query()->create([
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => true,
        'estado' => 'em_curso',
    ]);

    expect(app(AnoLectivoService::class)->defaultId())->toBe($anoCentral->getKey());
});

it('não copia o ano lectivo para uma base tenant', function () {
    $anoCentral = AnoLectivo::query()->create([
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => true,
        'estado' => 'em_curso',
    ]);

    app(AnoLectivoConsistencyService::class)->sincronizar();

    expect(AnoLectivo::query()->whereKey($anoCentral->id)->first()->activo)->toBeTrue();
});
