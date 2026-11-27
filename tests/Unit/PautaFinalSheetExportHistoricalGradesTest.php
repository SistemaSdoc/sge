<?php

use App\Exports\PautaFinal\PautaFinalTemplateExcelExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

uses(TestCase::class);

it('includes 10th- and 11th-grade marks beside current final grades', function (): void {
    $templatePath = resource_path('templates/pautas/classes/13-classe/turnos/tarde/final.xlsx');

    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [
            'institution' => 'ITCL',
            'class' => '13.ª Classe',
            'area' => 'Informática',
            'course' => 'Técnico de Informática de Gestão',
            'year' => '2025/2026',
            'room' => '',
        ],
        disciplines: [[
            'id' => 'project',
            'name' => 'Projecto Tecnológico',
            'sigla' => 'PT',
        ], [
            'id' => 'portuguese',
            'name' => 'Língua Portuguesa',
            'sigla' => 'LP',
        ]],
        students: [[
            'name' => 'Stelvio Marques',
            'grades' => [
                'project' => ['mt1' => 13, 'mt2' => 14, 'mt3' => 15, 'mf' => 14, 'faltas' => 0],
                'portuguese' => ['mt1' => 12, 'mt2' => 15, 'mt3' => 13, 'mf' => 14, 'faltas' => 1],
            ],
            'previous_grades' => [
                'project' => 11,
                'portuguese' => 14.6,
            ],
            'result' => 'transita',
            'disciplines_recurso' => [],
        ], [
            'name' => 'Aluno Incompleto',
            'grades' => [],
            'previous_grades' => [],
            'result' => 'incompleto',
            'disciplines_recurso' => [],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_historico_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('H4')->getValue())->toBe('ITCL')
            ->and($sheet->getCell('A6')->getValue())->toBe('PAUTA DE APROVEITAMENTO  ANO LECTIVO : 2025/2026')
            ->and($sheet->getCell('BB14')->getValue())->toBe('APTO');
    } finally {
        unlink($outputPath);
    }
});
