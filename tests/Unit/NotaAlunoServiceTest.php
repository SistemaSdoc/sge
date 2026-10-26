<?php

use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\Core\RegraAcademicaService;
use App\Services\Tenant\NotaAlunoService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

uses(TestCase::class);

test('nota de recurso gravada fica visível sem status de pauta', function (): void {
    $service = new NotaAlunoService(
        Mockery::mock(AnoLectivoResolverService::class),
        Mockery::mock(RegraAcademicaService::class),
    );
    $method = new ReflectionMethod($service, 'notaVisivelParaAluno');
    $method->setAccessible(true);

    $notaRecurso = new Nota;
    $notaRecurso->forceFill(['periodo' => 4]);
    $notaTrimestral = new Nota;
    $notaTrimestral->forceFill(['periodo' => 3]);
    $statusRascunho = new PautaStatus;
    $statusRascunho->forceFill(['status' => 'rascunho']);
    $statusFinalizado = new PautaStatus;
    $statusFinalizado->forceFill(['status' => 'finalizada']);

    expect($method->invoke($service, $notaRecurso, null))->toBeTrue()
        ->and($method->invoke($service, $notaTrimestral, null))->toBeFalse()
        ->and($method->invoke($service, $notaTrimestral, $statusRascunho))->toBeFalse()
        ->and($method->invoke($service, $notaTrimestral, $statusFinalizado))->toBeTrue();
});

test('resultado da pauta final permanece independente do resultado do recurso', function (
    string $situacaoAcademica,
    ?array $resultadoRecurso,
    string $situacaoEsperada,
    ?string $situacaoRecursoEsperada,
    ?string $situacaoDisciplinaEsperada,
): void {
    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('notas', collect());
    $resultadoAcademico = [
        'situacao' => $situacaoAcademica,
        'detalhes' => [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'media_final' => 8.5,
            'situacao' => $situacaoAcademica === 'recurso' ? 'recurso' : $situacaoAcademica,
        ]],
    ];

    $regraAcademicaService = Mockery::mock(RegraAcademicaService::class);
    $regraAcademicaService
        ->shouldReceive('resolverSituacaoAcademica')
        ->once()
        ->with($turmaAluno)
        ->andReturn($resultadoAcademico);

    if ($resultadoRecurso !== null) {
        $regraAcademicaService
            ->shouldReceive('resolverSituacaoRecurso')
            ->once()
            ->with($turmaAluno)
            ->andReturn($resultadoRecurso);
    } else {
        $regraAcademicaService
            ->shouldReceive('resolverSituacaoRecurso')
            ->once()
            ->with($turmaAluno)
            ->andReturn(['situacao' => 'pendente', 'detalhes' => []]);
    }

    $service = new NotaAlunoService(
        Mockery::mock(AnoLectivoResolverService::class),
        $regraAcademicaService,
    );
    $disciplina = (object) [
        'id' => 'disciplina-1',
        'nome' => 'Matemática',
        'sigla' => 'MAT',
    ];
    $tdp = (object) [
        'classeTurnoDisciplina' => (object) ['disciplina' => $disciplina],
    ];
    $method = new ReflectionMethod($service, 'resolverResultadoFinal');
    $method->setAccessible(true);

    $notasPorDisciplina = $situacaoDisciplinaEsperada !== null
        ? collect(['disciplina-1' => collect([(object) ['periodo' => 4, 'media_trimestral' => 12]])])
        : collect();
    $resultado = $method->invoke($service, $turmaAluno, new Collection([$tdp]), $notasPorDisciplina);

    expect($resultado['situacao'])->toBe($situacaoEsperada)
        ->and($resultado['situacao_academica'])->toBe($situacaoAcademica)
        ->and($resultado['situacao_recurso'])->toBe($situacaoRecursoEsperada)
        ->and($resultado['disciplinas'][0]['sigla'])->toBe('MAT')
        ->and($resultado['disciplinas'][0]['situacao_recurso'])->toBe($situacaoDisciplinaEsperada);
})->with([
    'transita diretamente' => [
        'transita',
        null,
        'transita',
        'pendente',
        null,
    ],
    'transita com deficiência' => [
        'transita_com_deficiencia',
        null,
        'transita_com_deficiencia',
        'pendente',
        null,
    ],
    'recurso por concluir' => [
        'recurso',
        [
            'situacao' => 'pendente',
            'detalhes' => [],
        ],
        'recurso',
        'pendente',
        null,
    ],
    'aprovado no recurso' => [
        'recurso',
        [
            'situacao' => 'aprovado_recurso',
            'detalhes' => [[
                'disciplina_id' => 'disciplina-1',
                'situacao' => 'aprovado_recurso',
                'media_recurso' => 12,
            ]],
        ],
        'recurso',
        'aprovado_recurso',
        'aprovado_recurso',
    ],
    'reprovado no recurso' => [
        'recurso',
        [
            'situacao' => 'reprovado_recurso',
            'detalhes' => [[
                'disciplina_id' => 'disciplina-1',
                'situacao' => 'reprovado_recurso',
                'media_recurso' => 8,
            ]],
        ],
        'recurso',
        'reprovado_recurso',
        'reprovado_recurso',
    ],
]);

test('dados finais do aluno mantêm os resultados de pauta final e recurso separados', function (): void {
    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('notas', collect());
    $resultadoAcademico = [
        'situacao' => 'recurso',
        'detalhes' => [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'media_final' => 7,
            'situacao' => 'recurso',
        ]],
    ];

    $regraAcademicaService = Mockery::mock(RegraAcademicaService::class);
    $regraAcademicaService
        ->shouldReceive('resolverSituacaoAcademica')
        ->once()
        ->with($turmaAluno)
        ->andReturn($resultadoAcademico);
    $regraAcademicaService
        ->shouldReceive('resolverSituacaoRecurso')
        ->once()
        ->with($turmaAluno)
        ->andReturn([
            'situacao' => 'aprovado_recurso',
            'detalhes' => [[
                'disciplina_id' => 'disciplina-1',
                'situacao' => 'aprovado_recurso',
                'media_recurso' => 12,
            ]],
        ]);

    $service = new NotaAlunoService(
        Mockery::mock(AnoLectivoResolverService::class),
        $regraAcademicaService,
    );
    $disciplina = (object) [
        'id' => 'disciplina-1',
        'nome' => 'Matemática',
        'sigla' => 'MAT',
    ];
    $tdp = (object) [
        'classeTurnoDisciplina' => (object) ['disciplina' => $disciplina],
    ];
    $notasPorDisciplina = collect([
        'disciplina-1' => collect([
            (object) ['periodo' => 1, 'media_trimestral' => 9.4, 'faltas' => 2],
            (object) ['periodo' => 2, 'media_trimestral' => 10.4, 'faltas' => 3],
            (object) ['periodo' => 3, 'media_trimestral' => 11.4, 'faltas' => 1],
            (object) ['periodo' => 4, 'media_trimestral' => 12, 'faltas' => 0],
        ]),
    ]);

    $method = new ReflectionMethod($service, 'resolverResultadoFinal');
    $method->setAccessible(true);
    $resultado = $method->invoke($service, $turmaAluno, new Collection([$tdp]), $notasPorDisciplina);

    expect($resultado['disciplinas'][0])
        ->toMatchArray([
            'situacao' => 'recurso',
            'mt1' => 9.0,
            'mt2' => 10.0,
            'mt3' => 11.0,
            'total_faltas' => 6,
            'mfd' => 10,
            'nota_recurso' => 12.0,
            'situacao_recurso' => 'aprovado_recurso',
        ]);
});
