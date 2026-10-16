<?php

use App\Exports\ListaAlunosTurmaExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

uses(TestCase::class);

it('fills the roster template and extends it for classes with more than 22 students', function (): void {
    $alunos = array_reverse(array_map(fn (int $numero) => [
        'numero' => $numero,
        'nome' => "Aluno {$numero}",
        'processo' => str_pad((string) $numero, 5, '0', STR_PAD_LEFT),
        'idade' => 18,
        'sexo' => 'Masculino',
        'contacto' => '923000000',
    ], range(1, 24)));

    $path = (new ListaAlunosTurmaExport)->gerar([
        'instituicao' => 'Instituto de Teste',
        'director' => 'Ana Directora',
        'curso' => 'Informática',
        'ano_lectivo' => '2025/26',
        'classe' => '10ª',
        'turma' => '10A',
        'sala' => '2',
    ], $alunos);

    try {
        $sheet = IOFactory::load($path)->getActiveSheet();

        expect($sheet->getCell('A4')->getValue())->toBe('INSTITUTO DE TESTE');
        expect($sheet->getStyle('A12')->getFont()->getName())->toBe('Arial');
        expect($sheet->getStyle('A12')->getFont()->getSize())->toBe(14.0);
        expect($sheet->getStyle('A12')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('B15')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('B16')->getFont()->getSize())->toBe(10.0);
        expect($sheet->getStyle('B16')->getFont()->getBold())->toBeFalse();
        expect($sheet->getCell('A8')->getValue())->toBe('Ana Directora');
        expect($sheet->getCell('A12')->getValue())->toBe('INFORMÁTICA');
        expect($sheet->getCell('A13')->getValue())->toBe('10ª CLASSE ; TURMA: 10A ; SALA: 2');
        expect($sheet->getCell('C13')->getValue())->toBe('ANO LECTIVO: 2025/26');
        expect($sheet->getCell('B16')->getValue())->toBe('Aluno 1');
        expect($sheet->getCell('A16')->getValue())->toBe(1);
        expect($sheet->getCell('C16')->getValue())->toBe('00001');
        expect($sheet->getCell('D16')->getValue())->toBe(18);
        expect($sheet->getCell('E16')->getValue())->toBe('Masculino');
        expect($sheet->getCell('F16')->getValue())->toBe('923000000');
        expect($sheet->getCell('B37')->getStyle()->getAlignment()->getHorizontal())->toBe('left');
        expect($sheet->getCell('B38')->getStyle()->getAlignment()->getHorizontal())->toBe('left');
        expect($sheet->getCell('B39')->getStyle()->getAlignment()->getHorizontal())->toBe('left');
        expect($sheet->getCell('B39')->getValue())->toBe('Aluno 24');
        expect($sheet->getCell('A39')->getValue())->toBe(24);
        expect($sheet->getPageSetup()->getPrintArea())->toBe('A1:G39');
        expect($sheet->getDrawingCollection())->toHaveCount(1);
    } finally {
        unlink($path);
    }
});
