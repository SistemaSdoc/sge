<?php

use App\Http\Requests\Tenant\CursoTutelado\StoreCursoTuteladoRequest;
use App\Http\Requests\Tenant\CursoTutelado\UpdateCursoTuteladoRequest;
use ReflectionMethod;

it('normalizes tutela propria to an empty tutor id before validation', function () {
    $request = new StoreCursoTuteladoRequest;
    $request->merge([
        'curso_id' => null,
        'nome' => 'Curso Teste',
        'duracao_anos' => 4,
        'nivel_ensino_id' => '00000000-0000-0000-0000-000000000001',
        'classe_ids' => ['00000000-0000-0000-0000-000000000002'],
        'tenant_tutor_id' => 'propria',
    ]);

    $method = new ReflectionMethod(StoreCursoTuteladoRequest::class, 'prepareForValidation');
    $method->setAccessible(true);
    $method->invoke($request);

    expect($request->input('tenant_tutor_id'))->toBe('');
});

it('does not expose central course fields in the update contract', function () {
    $rules = (new UpdateCursoTuteladoRequest)->rules();

    expect($rules)->toHaveKeys(['tenant_tutor_id', 'nivel_ensino_id', 'classes'])
        ->not->toHaveKeys(['nome', 'duracao_anos']);
});
