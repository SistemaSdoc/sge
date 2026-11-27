<?php

use App\Services\Tenant\Pauta\Exports\Final\PautaFinalStudentMapper;
use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('sorts final pauta students alphabetically without database collation differences', function (): void {
    $mapper = new PautaFinalStudentMapper(new PautaFinalDisciplineMapper);
    $students = $mapper->mapearAlunos(
        alunosPauta: new Collection([
            ['aluno_id' => '1', 'nome' => 'Josimar'],
            ['aluno_id' => '2', 'nome' => 'José'],
        ]),
        disciplinas: [],
        historicoNotas: ['disciplinas' => [], 'alunos' => []],
        numerosProcesso: new Collection,
        ordemClasse: 0,
    );

    expect(array_column($students, 'name'))->toBe(['José', 'Josimar']);
});
