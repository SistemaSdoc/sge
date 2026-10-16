<?php

use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;

test('deduplicates PAP course filters by central course across institutions', function (): void {
    $service = new GrupoPapViewService(Mockery::mock(CrossTenantAccessService::class));

    $options = $service->courseFilterOptions(collect([
        [
            'id' => 'institute-course-link',
            'curso_id' => 'central-informatica',
            'nome' => 'Informática',
            'instituicao_id' => 'institute',
        ],
        [
            'id' => 'school-course-link',
            'curso_id' => 'central-informatica',
            'nome' => 'Informática',
            'instituicao_id' => 'school',
        ],
        [
            'id' => 'accounting-course-link',
            'curso_id' => 'central-contabilidade',
            'nome' => 'Contabilidade',
            'instituicao_id' => 'institute',
        ],
        [
            'id' => 'course-without-central-link',
            'curso_id' => null,
            'nome' => 'Curso sem vínculo',
            'instituicao_id' => 'institute',
        ],
    ]));

    expect($options)->toHaveCount(2)
        ->and($options->firstWhere('id', 'central-informatica'))
        ->toBe(['id' => 'central-informatica', 'nome' => 'Informática']);
});
