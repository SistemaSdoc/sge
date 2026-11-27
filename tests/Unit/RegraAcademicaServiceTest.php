<?php

use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Disciplina as DisciplinaModel;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Services\Tenant\Core\RegraAcademica\Contexto\Contexto;
use App\Services\Tenant\Core\RegraAcademica\Disciplina\Disciplina;
use App\Services\Tenant\Core\RegraAcademica\Recurso\Recurso;
use App\Services\Tenant\Core\RegraAcademica\Recurso\RecursoStatusResolver;
use App\Services\Tenant\Core\RegraAcademica\RegraAplicavel\RegraAplicavel;
use App\Services\Tenant\Core\RegraAcademica\Resultado\Resultado;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

uses(TestCase::class);

test('resultado académico usa apenas a média de MT1, MT2 e MT3', function (): void {
    $disciplina = new DisciplinaModel;
    $disciplina->forceFill(['id' => 'disciplina-1', 'nome' => 'Matemática']);

    $classeTurnoDisciplina = new ClasseTurnoDisciplina;
    $classeTurnoDisciplina->forceFill(['disciplina_id' => $disciplina->id]);
    $classeTurnoDisciplina->setRelation('disciplina', $disciplina);

    $tdp = new TurmaDisciplinaProfessor;
    $tdp->forceFill(['id' => 'tdp-1']);
    $tdp->setRelation('classeTurnoDisciplina', $classeTurnoDisciplina);

    $turma = new Turma;
    $turma->setRelation('turmaDisciplinaProfessor', new Collection([$tdp]));

    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('turma', $turma);
    $turmaAluno->setRelation('notas', new Collection([
        notaDoPeriodo($tdp, 1, 9),
        notaDoPeriodo($tdp, 2, 9),
        notaDoPeriodo($tdp, 3, 9, mediaFinal: 18),
    ]));

    $contexto = Mockery::mock(Contexto::class);
    $contexto->shouldReceive('forAluno')
        ->once()
        ->with($turmaAluno)
        ->andReturn([
            'classe_actual' => (object) ['id' => 'classe-1'],
            'eh_ultima_classe' => true,
            'disciplinas_proxima_classe' => null,
        ]);

    $regraAplicavel = Mockery::mock(RegraAplicavel::class);
    $regraAplicavel->shouldReceive('resolve')->once()->andReturn(null);

    $avaliadorDisciplina = Mockery::mock(Disciplina::class);
    $avaliadorDisciplina->shouldReceive('avaliar')
        ->once()
        ->with('disciplina-1', 9.0, 10.0, true, true, null)
        ->andReturn([
            'negativa' => true,
            'situacao' => 'recurso',
            'continua' => false,
        ]);

    $resultado = Mockery::mock(Resultado::class);
    $resultado->shouldReceive('resolver')
        ->once()
        ->andReturn(['situacao' => 'recurso', 'mensagem' => 'Aluno vai ao recurso.']);
    $resultado->shouldReceive('construir')
        ->once()
        ->with('recurso', 'Aluno vai ao recurso.', Mockery::type('array'))
        ->andReturn(['situacao' => 'recurso', 'detalhes' => []]);

    $service = new RegraAcademicaService(
        $contexto,
        $regraAplicavel,
        $avaliadorDisciplina,
        $resultado,
        Mockery::mock(Recurso::class),
    );

    expect($service->resolverSituacaoAcademica($turmaAluno)['situacao'])->toBe('recurso');
});

test('resultado de recurso exige MFD inferior a 10 e avalia nota do recurso no limiar de 10', function (
    array $mediasTrimestrais,
    float $notaRecurso,
    string $situacaoEsperada,
): void {
    $disciplina = new DisciplinaModel;
    $disciplina->forceFill(['id' => 'disciplina-1', 'nome' => 'Matemática']);

    $classeTurnoDisciplina = new ClasseTurnoDisciplina;
    $classeTurnoDisciplina->forceFill(['disciplina_id' => $disciplina->id]);
    $classeTurnoDisciplina->setRelation('disciplina', $disciplina);

    $tdp = new TurmaDisciplinaProfessor;
    $tdp->forceFill(['id' => 'tdp-1']);
    $tdp->setRelation('classeTurnoDisciplina', $classeTurnoDisciplina);

    $turma = new Turma;
    $turma->setRelation('cursoClasseTurno', (object) [
        'cursoClasse' => (object) ['classe' => (object) ['id' => 'classe-1']],
    ]);

    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('turma', $turma);
    $turmaAluno->setRelation('notas', new Collection([
        notaDoPeriodo($tdp, 1, $mediasTrimestrais[0]),
        notaDoPeriodo($tdp, 2, $mediasTrimestrais[1]),
        notaDoPeriodo($tdp, 3, $mediasTrimestrais[2]),
        notaDoPeriodo($tdp, 4, $notaRecurso),
    ]));

    $resultado = (new RecursoStatusResolver)->resolver($turmaAluno);

    expect($resultado['situacao'])->toBe($situacaoEsperada);

    if ($situacaoEsperada === 'pendente') {
        expect($resultado['detalhes'])->toBeEmpty();

        return;
    }

    expect(collect($resultado['detalhes'])->first())
        ->toMatchArray([
            'disciplina_id' => 'disciplina-1',
            'media_final' => array_sum($mediasTrimestrais) / 3,
            'media_recurso' => $notaRecurso,
            'situacao' => $situacaoEsperada,
        ]);
})->with([
    'nota de recurso igual ou superior a 10 aprova' => [[9, 9, 9], 12, 'aprovado_recurso'],
    'nota de recurso inferior a 10 reprova' => [[9, 9, 9], 8, 'reprovado_recurso'],
    'MFD igual a 10 não entra em recurso' => [[10, 10, 10], 20, 'pendente'],
]);

test('resolver de recurso não recebe resultado da Pauta Final', function (): void {
    $turmaAluno = new TurmaAluno;
    $avaliacaoRecurso = [
        'situacao' => 'aprovado_recurso',
        'mensagem' => 'Aluno aprovado no recurso.',
        'detalhes' => [],
    ];

    $recurso = Mockery::mock(Recurso::class);
    $recurso->shouldReceive('avaliar')
        ->once()
        ->with($turmaAluno)
        ->andReturn($avaliacaoRecurso);

    $resultado = Mockery::mock(Resultado::class);
    $resultado->shouldReceive('construir')
        ->once()
        ->with('aprovado_recurso', 'Aluno aprovado no recurso.', [])
        ->andReturn($avaliacaoRecurso);

    $service = new RegraAcademicaService(
        Mockery::mock(Contexto::class),
        Mockery::mock(RegraAplicavel::class),
        Mockery::mock(Disciplina::class),
        $resultado,
        $recurso,
    );

    expect($service->resolverSituacaoRecurso($turmaAluno))->toBe($avaliacaoRecurso);
});

function notaDoPeriodo(
    TurmaDisciplinaProfessor $tdp,
    int $periodo,
    float $mediaTrimestral,
    ?float $mediaFinal = null,
): Nota {
    $nota = new Nota;
    $nota->forceFill([
        'periodo' => $periodo,
        'turma_disciplina_professor_id' => $tdp->id,
        'media_trimestral' => $mediaTrimestral,
        'media_final' => $mediaFinal,
        'situacao_trimestral' => 'APTO',
    ]);
    $nota->setRelation('turmaDisciplinaProfessor', $tdp);

    return $nota;
}
