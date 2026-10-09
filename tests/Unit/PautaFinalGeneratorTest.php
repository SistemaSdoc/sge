<?php

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService;
use App\Services\Tenant\Pauta\Generators\PautaFinalGenerator;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

uses(TestCase::class);

test('resultado da pauta final usa MFD e ignora a nota de recurso e a situação académica geral', function (
    array $mediasTrimestrais,
    float $notaRecurso,
    string $situacaoEsperada,
): void {
    $nota = fn (int $periodo, float $media): Nota => tap(new Nota, function (Nota $nota) use ($periodo, $media): void {
        $nota->forceFill([
            'periodo' => $periodo,
            'turma_disciplina_professor_id' => 'tdp-1',
            'media_trimestral' => $media,
        ]);
    });

    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('notas', new Collection([
        $nota(1, $mediasTrimestrais[0]),
        $nota(2, $mediasTrimestrais[1]),
        $nota(3, $mediasTrimestrais[2]),
        $nota(4, $notaRecurso),
    ]));
    $turmaAluno->setRelation('aluno', (object) [
        'id' => 'aluno-1',
        'inscricao' => (object) ['candidato' => (object) ['nome' => 'Aluno Teste']],
    ]);

    $regraAcademicaService = Mockery::mock(RegraAcademicaService::class);
    $regraAcademicaService->shouldNotReceive('resolverSituacaoAcademica');
    app()->instance(RegraAcademicaService::class, $regraAcademicaService);

    $generator = app(PautaFinalGenerator::class);
    $method = new ReflectionMethod($generator, 'montarAluno');
    $method->setAccessible(true);

    $aluno = $method->invoke($generator, $turmaAluno, 1, collect([
        ['id' => 'disciplina-1', 'tdp_id' => 'tdp-1'],
    ]));

    $situacaoDisciplinaEsperada = $situacaoEsperada === 'recurso' ? 'recurso' : 'transita';

    expect($aluno['resultado'])->toBe($situacaoEsperada)
        ->and($aluno['notas']['disciplina-1']['mf'])->toBe(round(array_sum($mediasTrimestrais) / 3, 0, PHP_ROUND_HALF_UP))
        ->and($aluno['notas']['disciplina-1']['situacao'])->toBe($situacaoDisciplinaEsperada);
})->with([
    'MFD abaixo de 10 continua em recurso com nota 4 alta' => [[9, 9, 9], 20, 'recurso'],
    'MFD igual a 10 transita apesar de nota 4 baixa' => [[10, 10, 10], 0, 'transita'],
]);
