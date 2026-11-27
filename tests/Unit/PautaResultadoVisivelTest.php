<?php

use App\Exports\MiniPauta\MiniPautaExcelExport;
use App\Exports\PautaFinal\PautaFinalTemplateExcelExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Tests\TestCase;

uses(TestCase::class);

test('template-based pauta and mini pauta exports leave incomplete results blank', function (): void {
    $templatePath = resource_path('templates/pautas/classes/13-classe/turnos/tarde/final.xlsx');

    $exports = [
        'P.FINAL' => new PautaFinalTemplateExcelExport(
            templatePath: $templatePath,
            header: [
                'institution' => 'Instituto de Teste',
                'class' => '13.ª Classe',
                'area' => 'Informática',
                'course' => 'Técnico de Informática de Gestão',
                'year' => '2025/2026',
                'room' => '7',
            ],
            disciplines: [[
                'id' => 'discipline-1',
                'name' => 'Projecto Tecnológico',
                'sigla' => 'PT',
            ]],
            students: [[
                'name' => 'Aluno Incompleto',
                'grades' => [
                    'discipline-1' => ['mt1' => null, 'mt2' => null, 'mt3' => null, 'mf' => null, 'faltas' => null],
                ],
                'previous_grades' => [],
                'result' => 'incompleto',
                'disciplines_recurso' => [],
            ]],
        ),
        'MAT' => new MiniPautaExcelExport(
            disciplinas: [[
                'nome' => 'Matemática',
                'sigla' => 'MAT',
                'alunos' => [[
                    'numero' => 1,
                    'nome' => 'Aluno Incompleto',
                    'resultado' => 'INCOMPLETO',
                    'notas' => [],
                    'mfd' => null,
                    'linha_vermelha' => false,
                ]],
            ]],
            curso: 'Informática',
            turma: 'T1',
            anoLetivo: '2025/2026',
            instituicao: 'Instituto de Teste',
            sala: '',
            classe: '10.ª',
        ),
    ];

    foreach ($exports as $sheetName => $export) {
        $contents = Excel::raw($export, ExcelFormat::XLSX);
        $outputPath = tempnam(sys_get_temp_dir(), 'pauta_resultado_vazio_');
        file_put_contents($outputPath, $contents);

        try {
            $sheet = IOFactory::load($outputPath)->getSheetByName($sheetName);
            $resultCell = $sheetName === 'P.FINAL' ? 'BT15' : 'U11';

            expect($sheet)->not->toBeNull()
                ->and(in_array($sheet->getCell($resultCell)->getValue(), [null, ''], true))->toBeTrue();
        } finally {
            unlink($outputPath);
        }
    }
});

test('mini pauta export loads the workbook template and creates one sheet export per discipline', function (): void {
    $export = new MiniPautaExcelExport(
        disciplinas: [[
            'nome' => 'Matemática',
            'sigla' => 'MAT',
            'alunos' => [[
                'numero' => 1,
                'nome' => 'Aluno Teste',
                'resultado' => 'APTO',
                'notas' => [
                    1 => ['mac' => 12, 'npp' => 13, 'npt' => 14, 'mt' => 13, 'faltas' => 1, 'situacao' => null],
                ],
                'mfd' => 13,
                'linha_vermelha' => false,
            ]],
        ], [
            'nome' => 'Tecnologias de Informação',
            'sigla' => 'TIC',
            'alunos' => [[
                'numero' => 1,
                'nome' => 'Segundo Aluno',
                'resultado' => 'APTO',
                'notas' => [],
                'mfd' => null,
                'linha_vermelha' => false,
            ]],
        ]],
        curso: 'Informática',
        turma: 'T1',
        anoLetivo: '2025/2026',
        instituicao: 'Instituto de Teste',
        sala: 'A1',
        classe: '10.ª',
    );

    expect(method_exists($export, 'exportTemplate'))->toBeTrue()
        ->and($export->exportTemplate())->toBe(resource_path('templates/mini-pautas/mini-pauta-disciplinas.xlsx'))
        ->and($export->sheets())->toHaveCount(2)
        ->and($export->sheets()[0]->title())->toBe('MAT')
        ->and($export->sheets()[1]->title())->toBe('TIC');

    $contents = Excel::raw($export, ExcelFormat::XLSX);
    $outputPath = tempnam(sys_get_temp_dir(), 'mini_pauta_multiplas_folhas_');
    file_put_contents($outputPath, $contents);

    try {
        $workbook = IOFactory::load($outputPath);

        expect($workbook->getSheetNames())->toBe(['MAT', 'TIC'])
            ->and($workbook->getSheetByName('TIC')->getCell('A8')->getValue())->toBe('DISCIPLINA: TECNOLOGIAS DE INFORMAÇÃO')
            ->and($workbook->getSheetByName('TIC')->getCell('A11')->getValue())->toBe(1)
            ->and($workbook->getSheetByName('TIC')->getCell('B11')->getValue())->toBe('Segundo Aluno');
    } finally {
        unlink($outputPath);
    }
});

test('mini pauta keeps the fixed footer block intact when there are many students', function (): void {
    $alunos = [];
    for ($i = 1; $i <= 35; $i++) {
        $alunos[] = [
            'numero' => $i,
            'nome' => "Aluno {$i}",
            'resultado' => 'APTO',
            'notas' => [
                1 => ['mac' => 12, 'npp' => 13, 'npt' => 14, 'mt' => 13, 'faltas' => 1, 'situacao' => null],
            ],
            'mfd' => 13,
            'linha_vermelha' => false,
        ];
    }

    $export = new MiniPautaExcelExport(
        disciplinas: [[
            'nome' => 'Matemática',
            'sigla' => 'MAT',
            'alunos' => $alunos,
        ]],
        curso: 'Informática',
        turma: 'T1',
        anoLetivo: '2025/2026',
        instituicao: 'Instituto de Teste',
        sala: 'A1',
        classe: '10.ª',
    );

    $contents = Excel::raw($export, ExcelFormat::XLSX);
    $outputPath = tempnam(sys_get_temp_dir(), 'mini_pauta_footer_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('MAT');

        expect($sheet->getCell('A11')->getValue())->toBe(1)
            ->and($sheet->getCell('A45')->getValue())->toBe(35)
            ->and($sheet->getCell('A11')->getXfIndex())->toBe($sheet->getCell('A12')->getXfIndex())
            ->and($sheet->getStyle('B11')->getAlignment()->getHorizontal())->toBe(Alignment::HORIZONTAL_LEFT)
            ->and($sheet->getCell('B46')->getValue())->toBeNull()
            ->and($sheet->getCell('B47')->getValue())->toBeNull()
            ->and($sheet->getCell('B48')->getValue())->toBe('PROFESSOR:')
            ->and($sheet->getCell('A50')->getValue())->toBe('DISCIPLINA:  MATEMATICA')
            ->and($sheet->getCell('A51')->getValue())->toBe('Nº');
    } finally {
        unlink($outputPath);
    }
});
