<?php

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\User;
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

test('shows the tutor name for theme improvements and keeps the coordination label', function (): void {
    $tutor = (new User)->forceFill([
        'nome' => 'Ana Tutora',
        'instituicao_id' => 'instituicao-tutora',
    ]);

    $melhoriaDoTutor = (new HistoricoAprovacaoPap)->forceFill([
        'estado_novo' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
    ]);
    $melhoriaDoTutor->setRelation('utilizador', $tutor);

    $melhoriaDaCoordenacao = (new HistoricoAprovacaoPap)->forceFill([
        'estado_novo' => GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
    ]);
    $melhoriaDaCoordenacao->setRelation('utilizador', $tutor);

    $reenvio = (new HistoricoAprovacaoPap)->forceFill([
        'estado_novo' => GrupoPap::APROVACAO_SUBMETIDO,
    ]);
    $reenvio->setRelation('utilizador', $tutor);

    $grupoPap = new GrupoPap;
    $grupoPap->setRelation('historicoAprovacao', collect([$melhoriaDoTutor, $melhoriaDaCoordenacao, $reenvio]));

    $historico = (new GrupoPapViewService(Mockery::mock(CrossTenantAccessService::class)))
        ->history($grupoPap, 'instituicao-tutora', 'Informática', 'ITU');

    expect($historico->first()['utilizador']['nome'])->toBe('Ana Tutora')
        ->and($historico[1]['utilizador']['nome'])
        ->toBe('Grupo disciplinar do curso Informática do ITU')
        ->and($historico->last()['utilizador']['nome'])->toBe('Ana Tutora');
});
