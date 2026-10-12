<?php

use App\Http\Controllers\Tenant\ExportarPautaController;
use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService;
use Tests\TestCase;

uses(TestCase::class);

test('exportacao final mantem a media original depois do lancamento do recurso', function (): void {
    $controller = app(ExportarPautaController::class);
    $method = new ReflectionMethod($controller, 'montarNotaDisciplina');
    $method->setAccessible(true);

    $notas = collect([
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 1,
            'media_trimestral' => 8,
            'media_final' => 18,
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 2,
            'media_trimestral' => 9,
            'media_final' => 18,
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 3,
            'media_trimestral' => 10,
            'media_final' => 18,
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 4,
            'media_trimestral' => 18,
            'media_final' => 18,
        ]),
    ]);

    $disciplina = $method->invoke(
        $controller,
        ['nome' => 'Matemática', 'tdp_id' => 10],
        $notas->groupBy('turma_disciplina_professor_id'),
        false,
    );

    expect($disciplina['Matemática']['media_final'])->toBe(9.0);
});

test('exportacao final apresenta o resultado academico real e as disciplinas correspondentes', function (
    string $situacao,
    array $detalhes,
    string $resultadoEsperado,
): void {
    $regraAcademicaService = Mockery::mock(RegraAcademicaService::class);
    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('aluno', (object) [
        'id' => 1,
        'inscricao' => (object) [
            'candidato' => (object) ['nome' => 'Aluno de teste'],
        ],
    ]);
    $turmaAluno->setRelation('notas', collect([
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 1,
            'media_trimestral' => 8,
            'situacao_anual' => 'TRANSITA',
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 2,
            'media_trimestral' => 8,
            'situacao_anual' => 'TRANSITA',
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 3,
            'media_trimestral' => 8,
            'situacao_anual' => 'TRANSITA',
        ]),
        new Nota([
            'turma_disciplina_professor_id' => 10,
            'periodo' => 4,
            'media_trimestral' => 18,
            'situacao_anual' => 'TRANSITA',
        ]),
    ]));

    $regraAcademicaService
        ->shouldReceive('resolverSituacaoAcademica')
        ->once()
        ->with($turmaAluno)
        ->andReturn([
            'situacao' => $situacao,
            'detalhes' => $detalhes,
        ]);

    $controller = new ExportarPautaController($regraAcademicaService);
    $method = new ReflectionMethod($controller, 'montarDadosAluno');
    $method->setAccessible(true);

    $aluno = $method->invoke(
        $controller,
        $turmaAluno,
        0,
        collect([['id' => 'disciplina-1', 'nome' => 'Matemática', 'sigla' => 'MAT', 'tdp_id' => 10]]),
        false,
    );

    expect($aluno['resultado'])->toBe($resultadoEsperado);
})->with([
    'recurso lançado não altera o resultado da pauta final' => [
        'recurso',
        [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'situacao' => 'recurso',
        ]],
        'RECURSO: MAT',
    ],
    'transita com deficiência apresenta as disciplinas' => [
        'transita_com_deficiencia',
        [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'situacao' => 'transita_com_deficiencia',
        ]],
        'TRANSITA COM DEFICIÊNCIA: MAT',
    ],
    'aprovado sem deficiência apresenta transita' => [
        'transita',
        [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'situacao' => 'aprovado',
        ]],
        'TRANSITA',
    ],
    'reprovado apresenta não transita' => [
        'reprovado',
        [[
            'disciplina_id' => 'disciplina-1',
            'disciplina' => 'Matemática',
            'situacao' => 'reprovado',
        ]],
        'N/TRANSITA',
    ],
    'reprovado por faltas mantém EEF' => [
        'EEF',
        [],
        'EEF',
    ],
    'notas incompletas mantêm resultado incompleto' => [
        'incompleto',
        [],
        'INCOMPLETO',
    ],
]);
