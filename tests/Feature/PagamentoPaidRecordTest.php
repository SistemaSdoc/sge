<?php

use App\Http\Controllers\PagamentoController;
use Illuminate\Support\Str;

it('inclui itens anuais e unicos com mes zero no paid record', function () {
    $controller = new PagamentoController;
    $method = new \ReflectionMethod($controller, 'paidRecordDoAluno');
    $method->setAccessible(true);

    $alunoId = (string) Str::uuid();

    $collection = new \Illuminate\Database\Eloquent\Collection([
        (object) [
            'item_pagavel_id' => (string) Str::uuid(),
            'mes' => 0,
        ],
    ]);

    $query = Mockery::mock();
    $query->shouldReceive('where')->andReturnSelf();
    $query->shouldReceive('whereHas')->andReturnSelf();
    $query->shouldReceive('get')->andReturn($collection);

    Mockery::mock('alias:App\\Models\\PagamentoItem')
        ->shouldReceive('query')->andReturn($query);

    $paidRecord = $method->invoke($controller, $alunoId);

    expect($paidRecord)
        ->toHaveKey((string) $collection[0]->item_pagavel_id)
        ->and($paidRecord[$collection[0]->item_pagavel_id])
        ->toContain(0);
});
