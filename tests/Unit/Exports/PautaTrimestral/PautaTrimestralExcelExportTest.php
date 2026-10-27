<?php

use App\Exports\PautaTrimestral\PautaTrimestralExcelExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use Tests\TestCase;

uses(TestCase::class);

test('fills only the selected quarterly grade cells and preserves the source template', function (
    int $period,
    string $periodLabel,
): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx');
    $templateHash = hash_file('sha256', $templatePath);
    $disciplines = collect(range(1, 9))
        ->map(fn (int $index): array => [
            'id' => "discipline-{$index}",
            'name' => "Disciplina {$index}",
            'sigla' => "SIG{$index}",
            'tdp_id' => "tdp-{$index}",
        ])
        ->all();

    $students = [
        [
            'number' => 1,
            'name' => 'Zuleica de Teste',
            'result' => 'N/APTO',
            'grades' => array_combine(array_column($disciplines, 'tdp_id'), [9, 10, 11, 12, 13, 14, 15, 16, 17]),
        ],
        [
            'number' => 2,
            'name' => 'Alice de Teste',
            'result' => 'APTO',
            'grades' => array_fill_keys(array_column($disciplines, 'tdp_id'), null),
        ],
    ];

    $contents = Excel::raw(new PautaTrimestralExcelExport(
        templatePath: $templatePath,
        header: [
            'institution' => 'Instituto de Teste',
            'class' => '10ª Classe',
            'area' => 'Informática de Gestão e Sistemas Empresariais',
            'course' => 'TÉCNICO DE INFORMÁTICA',
            'year' => '2025/2026',
            'room' => '5',
            'period' => $period,
            'director' => 'Director de Teste',
        ],
        disciplines: $disciplines,
        students: $students,
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('A6')->getValue())->toBe('INSTITUTO DE TESTE')
            ->and($sheet->getCell('B9')->getValue())->toBe('10ª Classe')
            ->and($sheet->getCell('B10')->getValue())->toBe('AREA DE FORMAÇÃO: INFORMÁTICA DE GESTÃO E SISTEMAS EMPRESARIAIS')
            ->and($sheet->getStyle('B10')->getAlignment()->getShrinkToFit())->toBeTrue()
            ->and($sheet->getStyle('B10')->getAlignment()->getWrapText())->toBeFalse()
            ->and($sheet->getMergeCells())->toContain('B10:J10', 'K10:Z10')
            ->and($sheet->getStyle('B10')->getFont()->getSize())->toBe($sheet->getStyle('N10')->getFont()->getSize())
            ->and($sheet->getCell('AD8')->getValue())->toBe($periodLabel)
            ->and($sheet->getCell('AD9')->getValue())->toBe('Ano : 2025/2026')
            ->and($sheet->getCell('C11')->getValue())->toBe('SIG1')
            ->and($sheet->getCell('AA11')->getValue())->toBe('SIG9')
            ->and($sheet->getCell('A14')->getValue())->toBe(1)
            ->and($sheet->getCell('B14')->getValue())->toBe('Alice de Teste')
            ->and($sheet->getCell('C14')->getValue())->toBeNull()
            ->and($sheet->getCell('C13')->getValue())->toBe('MT'.$period)
            ->and($sheet->getStyle('C15')->getFont()->getSize())->toBe(12.0)
            ->and($sheet->getStyle('C15')->getFont()->getBold())->toBeTrue()
            ->and($sheet->getCell('A15')->getValue())->toBe(2)
            ->and($sheet->getCell('B15')->getValue())->toBe('Zuleica de Teste')
            ->and($sheet->getCell('C15')->getValue())->toBe(9)
            ->and($sheet->getStyle('C15')->getFont()->getColor()->getARGB())->toBe('FFFF0000')
            ->and($sheet->getCell('F15')->getValue())->toBe(10)
            ->and($sheet->getStyle('F15')->getFont()->getColor()->getARGB())->toBe('FF1411D9')
            ->and($sheet->getCell('AA15')->getValue())->toBe(17)
            ->and($sheet->getStyle('AA15')->getFont()->getColor()->getARGB())->toBe('FF1411D9')
            ->and($sheet->getCell('D14')->getValue())->toBeNull()
            ->and($sheet->getCell('E14')->getValue())->toBeNull()
            ->and($sheet->getStyle('A15')->getBorders()->getBottom()->getBorderStyle())->toBe('medium')
            ->and($sheet->getStyle('A15')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('A15')->getFont()->getBold())->toBeFalse()
            ->and($sheet->getStyle('A15')->getFont()->getSize())->toBe(11.0)
            ->and($sheet->getStyle('B15')->getBorders()->getBottom()->getBorderStyle())->not->toBe('medium')
            ->and($sheet->getStyle('AD15')->getBorders()->getBottom()->getBorderStyle())->toBe('hair')
            ->and($sheet->getCell('AD14')->getValue())->toBe('APTO')
            ->and($sheet->getStyle('AD14')->getFont()->getColor()->getARGB())->toBe('FF1411D9')
            ->and($sheet->getStyle('AD14')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('AD14')->getAlignment()->getVertical())->toBe('center')
            ->and($sheet->getCell('AD15')->getValue())->toBe('N/APTO')
            ->and($sheet->getStyle('AD15')->getFont()->getColor()->getARGB())->toBe('FFFF0000')
            ->and($sheet->getCell('B3')->getValue())->toBe('Director de Teste')
            ->and($sheet->getCell('B22')->getValue())->toBeNull();
    } finally {
        unlink($outputPath);
    }

    expect(hash_file('sha256', $templatePath))->toBe($templateHash);
})->with([
    'primeiro trimestre' => [1, 'I TRIMESTRE'],
    'segundo trimestre' => [2, 'II TRIMESTRE'],
    'terceiro trimestre' => [3, 'III TRIMESTRE'],
]);

test('resizes the template to fit a class with fewer disciplines', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx');
    $disciplines = [
        ['id' => 'discipline-1', 'name' => 'Disciplina Um', 'sigla' => 'D1', 'tdp_id' => 'tdp-1'],
        ['id' => 'discipline-2', 'name' => 'Disciplina Dois', 'sigla' => 'D2', 'tdp_id' => 'tdp-2'],
    ];
    $contents = Excel::raw(new PautaTrimestralExcelExport(
        templatePath: $templatePath,
        header: ['period' => 1],
        disciplines: $disciplines,
        students: [[
            'number' => 1,
            'name' => 'Aluno Teste',
            'result' => 'APTO',
            'grades' => ['tdp-1' => 14, 'tdp-2' => 16],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');

        $institutionRange = collect($sheet->getMergeCells())
            ->first(fn (string $range): bool => str_starts_with($range, 'A4:'));
        [$institutionStart, $institutionEnd] = explode(':', $institutionRange);
        [$institutionStartColumn] = Coordinate::coordinateFromString($institutionStart);
        [$institutionEndColumn] = Coordinate::coordinateFromString($institutionEnd);
        $startColumnIndex = Coordinate::columnIndexFromString($institutionStartColumn);
        $endColumnIndex = Coordinate::columnIndexFromString($institutionEndColumn);
        $drawing = $sheet->getDrawingCollection()[0];
        [$drawingColumn] = Coordinate::coordinateFromString($drawing->getCoordinates());
        $drawingColumnIndex = Coordinate::columnIndexFromString($drawingColumn);
        $defaultFont = $sheet->getParent()->getDefaultStyle()->getFont();
        $institutionWidth = 0;
        $drawingPosition = 0;

        for ($columnIndex = $startColumnIndex; $columnIndex <= $endColumnIndex; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $columnWidth = Drawing::cellDimensionToPixels(
                $sheet->getColumnDimension($column)->getWidth(),
                $defaultFont,
            );
            $institutionWidth += $columnWidth;

            if ($columnIndex < $drawingColumnIndex) {
                $drawingPosition += $columnWidth;
            }
        }

        $drawingPosition += $drawing->getOffsetX();

        expect($sheet)->not->toBeNull()
            ->and(abs(($drawingPosition + ($drawing->getWidth() / 2)) - ($institutionWidth / 2)))->toBeLessThanOrEqual(1)
            ->and($sheet->getCell('C11')->getValue())->toBe('D1')
            ->and($sheet->getCell('F11')->getValue())->toBe('D2')
            ->and($sheet->getCell('I11')->getFormattedValue())->toBe('ATI 10')
            ->and($sheet->getCell('I12')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getCell('B21')->getValue())->toBeNull()
            ->and($sheet->getCell('D21')->getValue())->toBeNull()
            ->and($sheet->getCell('C14')->getValue())->toBe(14)
            ->and($sheet->getCell('F14')->getValue())->toBe(16)
            ->and($sheet->getCell('I14')->getValue())->toBe('APTO')
            ->and($sheet->getCell('I8')->getValue())->toBe('I TRIMESTRE')
            ->and($sheet->getMergeCells())->not->toContain('I11:K11')
            ->and($sheet->getCell('D12')->getFormattedValue())->toBe('F. JUST');
    } finally {
        unlink($outputPath);
    }
});

test('adds formatted discipline blocks when a class has more disciplines than the template', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx');
    $disciplines = collect(range(1, 11))
        ->map(fn (int $index): array => [
            'id' => "discipline-{$index}",
            'name' => "Disciplina {$index}",
            'sigla' => "SIG{$index}",
            'tdp_id' => "tdp-{$index}",
        ])
        ->all();
    $students = [[
        'number' => 1,
        'name' => 'Aluno Teste',
        'grades' => array_combine(array_column($disciplines, 'tdp_id'), range(10, 20)),
    ]];
    $contents = Excel::raw(new PautaTrimestralExcelExport(
        templatePath: $templatePath,
        header: ['period' => 1],
        disciplines: $disciplines,
        students: $students,
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('AA11')->getValue())->toBe('SIG9')
            ->and($sheet->getCell('AD11')->getValue())->toBe('SIG10')
            ->and($sheet->getCell('AG11')->getValue())->toBe('SIG11')
            ->and($sheet->getCell('AD14')->getValue())->toBe(19)
            ->and($sheet->getCell('AG14')->getValue())->toBe(20)
            ->and($sheet->getCell('AJ12')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getMergeCells())->toContain('AD11:AF11', 'AG11:AI11', 'AJ12:AK12');
    } finally {
        unlink($outputPath);
    }
});

test('renumbers and displays two-digit student numbers in the trimestral export', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx');
    $disciplines = [[
        'id' => 'discipline-1',
        'name' => 'Disciplina Um',
        'sigla' => 'D1',
        'tdp_id' => 'tdp-1',
    ]];

    $students = collect(range(40, 1))
        ->map(fn (int $index): array => [
            'number' => 99,
            'name' => sprintf('Aluno %02d', $index),
            'result' => 'APTO',
            'grades' => ['tdp-1' => 14],
        ])
        ->all();

    $contents = Excel::raw(new PautaTrimestralExcelExport(
        templatePath: $templatePath,
        header: ['period' => 1],
        disciplines: $disciplines,
        students: $students,
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_template_number_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('A14')->getValue())->toBe(1)
            ->and($sheet->getCell('B14')->getValue())->toBe('Aluno 01')
            ->and($sheet->getCell('A23')->getValue())->toBe(10)
            ->and($sheet->getCell('B23')->getValue())->toBe('Aluno 10')
            ->and($sheet->getStyle('A23')->getAlignment()->getShrinkToFit())->toBeTrue()
            ->and($sheet->getCell('A53')->getValue())->toBe(40)
            ->and($sheet->getCell('B53')->getValue())->toBe('Aluno 40');
    } finally {
        unlink($outputPath);
    }
});

test('generates quarterly exports for every class, shift, and period template', function (): void {
    foreach ([10, 11, 12, 13] as $classNumber) {
        foreach (['tarde', 'noite'] as $shift) {
            foreach ([1, 2, 3] as $period) {
                $templatePath = resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shift}/trimestral.xlsx");
                $contents = Excel::raw(new PautaTrimestralExcelExport(
                    templatePath: $templatePath,
                    header: ['class' => "{$classNumber}.ª Classe", 'period' => $period, 'year' => '2026/2027'],
                    disciplines: [[
                        'id' => 'discipline-1',
                        'name' => 'Matemática',
                        'sigla' => 'MAT',
                        'tdp_id' => 'tdp-1',
                    ]],
                    students: [[
                        'number' => 999,
                        'name' => 'Áluno Nº 001 - São Tomé',
                        'result' => $period === 1 ? 'INCOMPLETO' : 'APTO',
                        'grades' => ['tdp-1' => 20],
                    ]],
                ), ExcelFormat::XLSX);

                $outputPath = tempnam(sys_get_temp_dir(), 'pauta_trimestral_matrix_');
                file_put_contents($outputPath, $contents);

                try {
                    $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');
                    $periodLabel = [1 => 'I', 2 => 'II', 3 => 'III'][$period].' TRIMESTRE';
                    $cells = collect($sheet->toArray(null, false, false, false))->flatten();
                    $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                    $resultColumn = null;

                    for ($columnIndex = 1; $columnIndex <= $lastColumn; $columnIndex++) {
                        $column = Coordinate::stringFromColumnIndex($columnIndex);

                        if (mb_strtoupper(trim((string) $sheet->getCell($column.'12')->getValue()), 'UTF-8') === 'RESULTADO') {
                            $resultColumn = $column;

                            break;
                        }
                    }

                    expect($sheet)->not->toBeNull()
                        ->and($cells)->toContain($periodLabel)
                        ->and($sheet->getCell('A14')->getValue())->toBe(1)
                        ->and($sheet->getCell('B14')->getValue())->toBe('Áluno Nº 001 - São Tomé')
                        ->and($sheet->getCell('C14')->getValue())->toBe(20)
                        ->and($resultColumn)->not->toBeNull()
                        ->and(in_array($sheet->getCell($resultColumn.'14')->getValue(), [null, ''], true))->toBe($period === 1);
                } finally {
                    unlink($outputPath);
                }
            }
        }
    }
});

test('rejects quarterly exports without disciplines or with an invalid period', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx');
    $student = ['number' => 1, 'name' => 'Aluno Teste', 'result' => 'APTO', 'grades' => []];

    expect(fn () => Excel::raw(new PautaTrimestralExcelExport(
        templatePath: $templatePath,
        header: ['period' => 1],
        disciplines: [],
        students: [$student],
    ), ExcelFormat::XLSX))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Excel::raw(new PautaTrimestralExcelExport(
            templatePath: $templatePath,
            header: ['period' => 4],
            disciplines: [['id' => 'discipline-1', 'name' => 'Matemática', 'sigla' => 'MAT', 'tdp_id' => 'tdp-1']],
            students: [$student],
        ), ExcelFormat::XLSX))->toThrow(InvalidArgumentException::class);
});

test('exports an empty quarterly roster without creating phantom student rows', function (): void {
    $contents = Excel::raw(new PautaTrimestralExcelExport(
        templatePath: resource_path('templates/pautas/classes/10-classe/turnos/tarde/trimestral.xlsx'),
        header: ['period' => 1],
        disciplines: [['id' => 'discipline-1', 'name' => 'Matemática', 'sigla' => 'MAT', 'tdp_id' => 'tdp-1']],
        students: [],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_trimestral_empty_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('I TRI');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('A14')->getValue())->toBeNull()
            ->and($sheet->getCell('B14')->getValue())->toBeNull();
    } finally {
        unlink($outputPath);
    }
});
