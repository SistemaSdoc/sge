<?php

use App\Exports\PautaFinal\PautaFinalTemplateExcelExport;
use App\Models\Tenant\Classe;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use App\Services\Tenant\Pauta\ResolvePautaTemplatePath;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

uses(TestCase::class);

test('writes previous-class final marks into the 10th-grade columns of the 11th-grade final template', function (): void {
    $templatePath = resource_path('templates/pautas/classes/11-classe/turnos/tarde/final.xlsx');
    $disciplines = collect([
        'L. PORTUGUESA',
        'L. INGLESA',
        'MATEMÁTICA',
        'T. L.P',
        'T. I. C.',
        'O . A . E .',
        'REDES DE COMP.',
        'I . A . G .',
        'N. DE DIREITO',
        'F. A . I .',
        'ED. FISÍCA',
    ])->map(fn (string $label, int $index): array => [
        'id' => "discipline-{$index}",
        'name' => match ($index) {
            6 => 'Redes de Computadores',
            8 => 'Noções de Direito',
            default => $label,
        },
        'sigla' => match ($index) {
            6 => 'RDC',
            8 => 'ND',
            default => $label,
        },
    ])->all();
    $grades = array_fill_keys(array_column($disciplines, 'id'), []);
    $grades['discipline-0'] = ['mt1' => 10, 'mt2' => 11, 'mt3' => 8, 'mf' => 10, 'faltas' => 4];

    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [
            'institution' => 'Instituto de Teste',
            'class' => '11.ª Classe',
            'area' => 'Informática',
            'course' => 'Técnico de Informática de Gestão',
            'year' => '2025/2026',
            'room' => '7',
            'director' => 'Director de Teste',
            'course_coordinator' => 'Coordenador do Curso Técnico de Informática de Gestão',
            'subdirector' => 'Subdirector de Teste',
        ],
        disciplines: $disciplines,
        students: [[
            'name' => 'Aluno Teste',
            'grades' => $grades,
            'previous_grades' => [
                'discipline-0' => 14,
                'discipline-1' => 12,
                'discipline-9' => 16,
                'discipline-10' => 18,
            ],
            'result' => 'transita',
            'disciplines_recurso' => [],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_11_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('C15')->getValue())->toBe(14.0)
            ->and($sheet->getCell('K15')->getValue())->toBe(12.0)
            ->and($sheet->getCell('BH15')->getValue())->toBe(16.0)
            ->and($sheet->getCell('BN15')->getValue())->toBe(18.0)
            ->and($sheet->getCell('D15')->getValue())->toBe(10.0)
            ->and($sheet->getCell('E15')->getValue())->toBe(11.0)
            ->and($sheet->getCell('F15')->getValue())->toBe(8.0)
            ->and($sheet->getCell('G15')->getValue())->toBe(10.0)
            ->and($sheet->getCell('H15')->getValue())->toBe(4.0)
            ->and($sheet->getCell('BV15')->getValue())->toBe('=SUM(G15,O15,V15,AC15,AJ15,AQ15,AV15,BA15,BF15,BL15,BR15)/11')
            ->and($sheet->getCell('A6')->getValue())->toBe('INSTITUTO DE TESTE')
            ->and($sheet->getCell('B9')->getValue())->toBe('Director de Teste')
            ->and($sheet->getCell('B10')->getValue())->toBe('11.ª Classe')
            ->and($sheet->getCell('B11')->getValue())->toBe('AREA DE FORMAÇÃO: INFORMÁTICA')
            ->and($sheet->getCell('AS11')->getValue())->toBe('CURSO: TÉCNICO DE INFORMÁTICA DE GESTÃO')
            ->and($sheet->getCell('BU10')->getValue())->toBe('2025/2026')
            ->and($sheet->getCell('BU11')->getValue())->toBe(7)
            ->and($sheet->getCell('AA20')->getValue())->toBe('Coordenador do Curso Técnico de Informática de Gestão')
            ->and($sheet->getCell('AY20')->getValue())->toBe('Subdirector de Teste')
            ->and($sheet->getMergeCells())->toContain('AA20:AX20')
            ->and($sheet->getStyle('AA20')->getAlignment()->getShrinkToFit())->toBeTrue()
            ->and(count($sheet->getDrawingCollection()))->toBe(1);
    } finally {
        unlink($outputPath);
    }
});

test('keeps a small discipline set in its matching template block', function (): void {
    $templatePath = resource_path('templates/pautas/classes/11-classe/turnos/tarde/final.xlsx');
    $discipline = ['id' => 'physical', 'name' => 'Educação Física', 'sigla' => 'Ed.Fisica'];
    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [],
        disciplines: [$discipline],
        students: [[
            'name' => 'Aluno Teste',
            'grades' => [
                'physical' => ['mt1' => 9, 'mt2' => 10, 'mt3' => 11, 'mf' => 10, 'faltas' => 2],
            ],
            'previous_grades' => ['physical' => 16],
            'result' => 'transita',
            'disciplines_recurso' => [],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_11_poucas_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('BN12')->getValue())->toBe('ED. FISÍCA')
            ->and($sheet->getCell('BN15')->getValue())->toBe(16.0)
            ->and($sheet->getCell('BO15')->getValue())->toBe(9.0)
            ->and($sheet->getCell('BR15')->getValue())->toBe(10.0)
            ->and($sheet->getCell('BS15')->getValue())->toBe(2.0)
            ->and($sheet->getCell('BT13')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getColumnDimension('BM')->getWidth())->toBeGreaterThanOrEqual(3.4)
            ->and($sheet->getStyle('BN12')->getFont()->getSize())->toBe(9.0)
            ->and($sheet->getStyle('BN12')->getAlignment()->getShrinkToFit())->toBeTrue()
            ->and($sheet->getStyle('BO15')->getFont()->getSize())->toBe(9.0)
            ->and($sheet->getCell('BN15')->getFormattedValue())->toBe('16');
    } finally {
        unlink($outputPath);
    }
});

test('adds an extra discipline block after the built-in 11th-grade subjects', function (): void {
    $templatePath = resource_path('templates/pautas/classes/11-classe/turnos/tarde/final.xlsx');
    $labels = [
        'L. PORTUGUESA', 'L. INGLESA', 'MATEMÁTICA', 'T. L.P', 'T. I. C.', 'O . A . E .',
        'REDES DE COMP.', 'I . A . G .', 'N. DE DIREITO', 'F. A . I .', 'ED. FISÍCA',
        'DISCIPLINA EXTRA',
    ];
    $disciplines = collect($labels)
        ->map(fn (string $label, int $index): array => [
            'id' => "discipline-{$index}",
            'name' => match ($index) {
                6 => 'Redes de Computadores',
                8 => 'Noções de Direito',
                default => $label,
            },
            'sigla' => match ($index) {
                6 => 'RC',
                8 => 'ND',
                default => $label,
            },
        ])
        ->all();

    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [
            'institution' => 'Instituto de Teste',
            'class' => '11.ª Classe',
            'area' => 'Informática',
            'course' => 'Técnico de Informática de Gestão',
            'year' => '2025/2026',
            'room' => '7',
        ],
        disciplines: $disciplines,
        students: [[
            'name' => 'Aluno Teste',
            'grades' => array_fill_keys(array_column($disciplines, 'id'), []),
            'previous_grades' => [],
            'result' => 'transita',
            'disciplines_recurso' => [],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_11_muitas_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('BT12')->getValue())->toBe('DISCIPLINA EXTRA')
            ->and($sheet->getMergeCells())->toContain('BT12:BY12')
            ->and($sheet->getCell('BZ13')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getCell('CB15')->getValue())->toBe('=SUM(G15,O15,V15,AC15,AJ15,AQ15,AV15,BA15,BF15,BL15,BR15,BX15)/12')
            ->and($sheet->getCell('BY13')->getFormattedValue())->toBe('F. Inj')
            ->and($sheet->getColumnDimension('BM')->getWidth())->toBeGreaterThanOrEqual(3.4)
            ->and($sheet->getCell('BY10')->getValue())->toBe('Ano:')
            ->and($sheet->getCell('CA10')->getValue())->toBe('2025/2026')
            ->and($sheet->getCell('BY11')->getValue())->toBe('Sala:')
            ->and($sheet->getCell('CA11')->getValue())->toBe(7);
    } finally {
        unlink($outputPath);
    }
});

test('fills the 12th-grade final template using the model row and historical grades for Stelvio Marques', function (): void {
    $templatePath = tempnam(sys_get_temp_dir(), 'pauta_12_');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('P.FINAL');
    $sheet->fromArray([
        ['REPÚBLICA DE ANGOLA'],
        ['MINISTÉRIO DA EDUCAÇÃO'],
        ['INSTITUTO TÉCNICO DE LÁNGUAS'],
        ['Ano: 2025/2026'],
        ['Curso: Informática'],
        ['11.ª Classe'],
    ], null, 'A1');
    $sheet->setCellValue('A21', null);
    $sheet->setCellValue('B21', null);
    $sheet->setCellValue('C21', null);
    $sheet->setCellValue('D21', null);
    $sheet->setCellValue('E21', null);
    $sheet->setCellValue('F21', null);
    $sheet->setCellValue('G21', null);
    $sheet->setCellValue('H21', null);
    $sheet->setCellValue('I21', null);
    $sheet->setCellValue('J21', null);
    $sheet->setCellValue('K21', null);
    $sheet->setCellValue('L21', null);
    $sheet->setCellValue('M21', null);
    $sheet->setCellValue('N21', null);
    $sheet->setCellValue('O21', null);
    $sheet->setCellValue('P21', null);
    $sheet->setCellValue('Q21', null);
    $sheet->setCellValue('R21', null);
    $sheet->setCellValue('S21', null);
    $sheet->setCellValue('T21', null);
    $sheet->setCellValue('U21', null);
    $sheet->setCellValue('V21', null);
    $sheet->setCellValue('W21', null);
    $sheet->setCellValue('X21', null);
    $sheet->setCellValue('Y21', null);
    $sheet->setCellValue('Z21', null);
    $sheet->setCellValue('AA21', null);
    $sheet->setCellValue('AB21', null);
    $sheet->setCellValue('AC21', null);
    $sheet->setCellValue('AD21', null);
    $sheet->setCellValue('AE21', null);
    $sheet->setCellValue('AF21', null);
    $sheet->setCellValue('AG21', null);
    $sheet->setCellValue('AH21', null);
    $sheet->setCellValue('AI21', null);
    $sheet->setCellValue('AJ21', null);
    $sheet->setCellValue('AK21', null);
    $sheet->setCellValue('AL21', null);
    $sheet->setCellValue('AM21', null);
    $sheet->setCellValue('AN21', null);
    $sheet->setCellValue('AO21', null);
    $sheet->setCellValue('AP21', null);
    $sheet->setCellValue('AQ21', null);
    $sheet->setCellValue('AR21', null);
    $sheet->setCellValue('AS21', null);
    $sheet->setCellValue('AT21', null);
    $sheet->setCellValue('AU21', null);
    $sheet->setCellValue('AV21', null);
    $sheet->setCellValue('AW21', null);
    $sheet->setCellValue('AX21', null);
    $sheet->setCellValue('AY21', null);
    $sheet->setCellValue('AZ21', null);
    $sheet->setCellValue('BA21', null);
    $sheet->setCellValue('BB21', null);
    $sheet->setCellValue('BC21', null);
    $sheet->setCellValue('BD21', null);
    $sheet->setCellValue('BE21', null);
    $sheet->setCellValue('BF21', null);
    $sheet->setCellValue('BG21', null);
    $sheet->setCellValue('BH21', null);
    $sheet->setCellValue('BI21', null);
    $sheet->setCellValue('BJ21', null);
    $sheet->setCellValue('BK21', null);
    $sheet->setCellValue('BL21', null);
    $sheet->setCellValue('BM21', null);
    $sheet->setCellValue('BL20', 'RESULTADO');
    $sheet->setCellValue('BM20', 'Q. HONRA');
    $sheet->setCellValue('A22', 'Data do Conselho de Turma:');
    $sheet->setCellValue('A10', 'Director fixo do template');
    $sheet->setCellValue('M11', 'INSTITUIÇÃO FIXA');
    $sheet->setCellValue('A13', 'ANO LECTIVO FIXO');
    $sheet->setCellValue('A15', 'ÁREA E CURSO FIXOS');
    $sheet->setCellValue('A16', 'TURNO FIXO');
    $sheet->setCellValue('BL18', 'SALA FIXA');
    $sheet->setCellValue('C26', 'DIRECTOR DE TURMA FIXO');
    $sheet->setCellValue('M26', 'COORDENADOR FIXO');
    $sheet->setCellValue('AK26', 'SUBDIRECTOR FIXO');
    $sheet->setCellValue('A20', 'Nº');
    $sheet->setCellValue('B20', 'Nº PROC');
    $sheet->setCellValue('C20', 'NOME');
    $sheet->setCellValue('D20', 'L.P');
    $sheet->setCellValue('E20', 'ING');
    $sheet->setCellValue('F20', 'FAI');
    $sheet->setCellValue('G20', 'ED. FISÍCA');
    $sheet->setCellValue('H20', 'N. DIREITO');
    $sheet->setCellValue('I20', 'REDES');
    $sheet->setCellValue('J20', 'BD');
    $sheet->setCellValue('K20', 'MAT 10ª');
    $sheet->setCellValue('L20', 'MAT 11ª');
    $sheet->setCellValue('M20', 'F. INJ');
    $sheet->setCellValue('N20', 'MF');
    $sheet->setCellValue('O20', 'EXAME');
    $sheet->setCellValue('P20', 'CA');
    $sheet->setCellValue('Q20', 'CFD');
    $sheet->setCellValue('R20', 'F. INJ');
    $sheet->setCellValue('S20', 'MFD');
    $sheet->setCellValue('T20', 'EXAME');
    $sheet->setCellValue('U20', 'MF');
    $sheet->setCellValue('V20', 'F. INJ');
    $sheet->setCellValue('W20', 'MF');
    $sheet->setCellValue('X20', 'EXAME');
    $sheet->setCellValue('Y20', 'CFD');
    $sheet->setCellValue('Z20', 'OAE 10ª');
    $sheet->setCellValue('AA20', 'OAE 11ª');
    $sheet->setCellValue('AB20', 'F. INJ');
    $sheet->setCellValue('AC20', 'MF');
    $sheet->setCellValue('AD20', 'EXAME');
    $sheet->setCellValue('AE20', 'CA');
    $sheet->setCellValue('AF20', 'CFD');
    $sheet->setCellValue('AG20', 'TIC 10ª');
    $sheet->setCellValue('AH20', 'TIC 11ª');
    $sheet->setCellValue('AI20', 'F. INJ');
    $sheet->setCellValue('AJ20', 'MF');
    $sheet->setCellValue('AK20', 'EXAME');
    $sheet->setCellValue('AL20', 'CA');
    $sheet->setCellValue('AM20', 'CFD');
    $sheet->setCellValue('AN20', 'IAG 11ª');
    $sheet->setCellValue('AO20', 'F. INJ');
    $sheet->setCellValue('AP20', 'MF');
    $sheet->setCellValue('AQ20', 'EXAME');
    $sheet->setCellValue('AS20', 'CFD');
    $sheet->setCellValue('AT20', 'F. INJ');
    $sheet->setCellValue('AU20', 'MF');
    $sheet->setCellValue('AV20', 'EXAME');
    $sheet->setCellValue('AW20', 'CFD');
    $sheet->setCellValue('AX20', 'TLP 10ª');
    $sheet->setCellValue('AY20', 'TLP 11ª');
    $sheet->setCellValue('AZ20', 'F. INJ');
    $sheet->setCellValue('BA20', 'MF');
    $sheet->setCellValue('BB20', 'EXAME');
    $sheet->setCellValue('BC20', 'CFD');
    $sheet->setCellValue('BD20', 'F. INJ');
    $sheet->setCellValue('BE20', 'MF');
    $sheet->setCellValue('BF20', 'EXAME');
    $sheet->setCellValue('BG20', 'CFD');
    $sheet->setCellValue('BH20', 'F. INJ');
    $sheet->setCellValue('BI20', 'MF');
    $sheet->setCellValue('BJ20', 'EXAME');
    $sheet->setCellValue('BK20', 'CFD');
    $sheet->setCellValue('BL20', 'RESULTADO');
    $sheet->setCellValue('BM20', 'Q. HONRA');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($templatePath.'.xlsx');

    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath.'.xlsx',
        header: [
            'institution' => 'ITCL',
            'class' => '12.ª Classe',
            'turma' => 'ATI',
            'area' => 'Informática',
            'course' => 'Técnico de Informática de Gestão',
            'year' => '2025/2026',
            'shift' => 'Tarde',
            'room' => '01',
            'director' => 'Directora Dinâmica',
            'class_coordinator' => null,
            'course_coordinator' => 'Coordenador Dinâmico',
            'subdirector' => 'Subdirector Dinâmico',
        ],
        disciplines: [[
            'id' => 'lp',
            'name' => 'Língua Portuguesa',
            'sigla' => 'LP',
        ], [
            'id' => 'ing',
            'name' => 'Inglês',
            'sigla' => 'ING',
        ], [
            'id' => 'fai',
            'name' => 'F.A.I.',
            'sigla' => 'FAI',
        ], [
            'id' => 'edfisica',
            'name' => 'Educação Física',
            'sigla' => 'ED.FIS',
        ], [
            'id' => 'direito',
            'name' => 'Noções de Direito',
            'sigla' => 'ND',
        ], [
            'id' => 'redes',
            'name' => 'Redes',
            'sigla' => 'RED',
        ], [
            'id' => 'bd',
            'name' => 'Base de Dados',
            'sigla' => 'BD',
        ], [
            'id' => 'mat',
            'name' => 'Matemática',
            'sigla' => 'MAT',
        ], [
            'id' => 'oae',
            'name' => 'O.A.E',
            'sigla' => 'OAE',
        ], [
            'id' => 'tic',
            'name' => 'T.I.C',
            'sigla' => 'TIC',
        ], [
            'id' => 'iag',
            'name' => 'I.A.G',
            'sigla' => 'IAG',
        ], [
            'id' => 'tlp',
            'name' => 'T.L.P',
            'sigla' => 'TLP',
        ], [
            'id' => 'direito12',
            'name' => 'Direito',
            'sigla' => 'DIR',
        ], [
            'id' => 'sitinfo',
            'name' => 'Sit. Informacional',
            'sigla' => 'SI',
        ], [
            'id' => 'emp',
            'name' => 'Empreendedorismo',
            'sigla' => 'EMP',
        ], [
            'id' => 'imei',
            'name' => 'IMEI',
            'sigla' => 'IMEI',
        ], [
            'id' => 'pt',
            'name' => 'Projecto Técnico',
            'sigla' => 'PT',
        ]],
        students: [[
            'number' => 1,
            'name' => 'Stelvio Marques',
            'grades' => [
                'mat' => ['mt1' => 12, 'mt2' => 13, 'mt3' => 14, 'mf' => 13, 'faltas' => 0],
                'oae' => ['mt1' => 10, 'mt2' => 12, 'mt3' => 11, 'mf' => 11, 'faltas' => 1],
                'tic' => ['mt1' => 14, 'mt2' => 15, 'mt3' => 14, 'mf' => 14, 'faltas' => 0],
                'iag' => ['mt1' => 9, 'mt2' => 11, 'mt3' => 12, 'mf' => 11, 'faltas' => 2],
                'tlp' => ['mt1' => 12, 'mt2' => 13, 'mt3' => 13, 'mf' => 12, 'faltas' => 1],
                'direito12' => ['mt1' => 14, 'mt2' => 15, 'mt3' => 15, 'mf' => 15, 'faltas' => 0],
                'sitinfo' => ['mt1' => 13, 'mt2' => 14, 'mt3' => 13, 'mf' => 13, 'faltas' => 0],
                'emp' => ['mt1' => 12, 'mt2' => 12, 'mt3' => 11, 'mf' => 12, 'faltas' => 0],
                'imei' => ['mt1' => 13, 'mt2' => 14, 'mt3' => 13, 'mf' => 13, 'faltas' => 0],
                'pt' => ['mt1' => 15, 'mt2' => 15, 'mt3' => 14, 'mf' => 15, 'faltas' => 0],
            ],
            'previous_grades' => [
                'lp' => 14,
                'ing' => 13,
                'fai' => 15,
                'edfisica' => 16,
                'direito' => 12,
                'redes' => 14,
                'bd' => 15,
                'mat' => 12,
                'mat-11' => 13,
                'oae' => 13,
                'tic' => 14,
                'iag' => 13,
                'tlp' => 15,
            ],
            'result' => 'transita',
            'disciplines_recurso' => [],
        ], [
            'number' => 2,
            'name' => 'Zuleica Teste',
            'grades' => [],
            'previous_grades' => [],
            'result' => 'incompleto',
            'disciplines_recurso' => [],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_12_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('A21')->getValue())->toBe(1)
            ->and($sheet->getCell('C21')->getValue())->toBe('Stelvio Marques')
            ->and($sheet->getCell('D21')->getValue())->toBe(14.0)
            ->and($sheet->getCell('K21')->getValue())->toBe(12.0)
            ->and($sheet->getCell('L21')->getValue())->toBe(13.0)
            ->and($sheet->getCell('Q21')->getValue())->toBe('=IF(N21="EEF","EEF",(K21+L21+N21)/3)')
            ->and($sheet->getCell('BL21')->getValue())->toBe('APTO')
            ->and($sheet->getCell('A22')->getValue())->toBe(2)
            ->and($sheet->getCell('C22')->getValue())->toBe('Zuleica Teste')
            ->and(in_array($sheet->getCell('BL22')->getValue(), [null, ''], true))->toBeTrue()
            ->and($sheet->getCell('A23')->getValue())->toBe('Data do Conselho de Turma:')
            ->and($sheet->getCell('A10')->getValue())->toBe('Directora Dinâmica')
            ->and($sheet->getCell('M11')->getValue())->toBe('ITCL')
            ->and($sheet->getCell('A13')->getValue())->toBe('PAUTA DE APROVEITAMENTO  ANO LECTIVO : 2025/2026')
            ->and($sheet->getCell('A15')->getValue())->toContain('ÁREA DE FORMAÇÃO : Técnico de Informática de Gestão')
            ->and($sheet->getCell('A16')->getValue())->toBe('( Regime: REGULAR-TARDE)')
            ->and($sheet->getCell('BL18')->getValue())->toBe('SALA : 01')
            ->and($sheet->getCell('C27')->getValue())->toBeNull()
            ->and($sheet->getCell('M27')->getValue())->toBe('Coordenador Dinâmico')
            ->and($sheet->getCell('AK27')->getValue())->toBe('Subdirector Dinâmico')
            ->and($sheet->getStyle('BL21')->getFont()->getColor()->getRGB())->toBe('1B5E20');
    } finally {
        unlink($outputPath);
        unlink($templatePath.'.xlsx');
    }
});

test('writes the 13th-grade final layout and grades from previous classes', function (): void {
    $disciplines = [
        ['id' => 'lp', 'name' => 'Língua Portuguesa', 'sigla' => 'L.Portuguesa'],
        ['id' => 'ing', 'name' => 'Inglês', 'sigla' => 'L.Inglesa'],
        ['id' => 'fai', 'name' => 'Formação de Atitudes Integradoras', 'sigla' => 'FAI'],
        ['id' => 'oae', 'name' => 'Organização e Administração de Empresas', 'sigla' => 'OAE'],
        ['id' => 'imei', 'name' => 'Instalação e Manutenção de Equipamentos Informáticos', 'sigla' => 'IMEI'],
        ['id' => 'direito', 'name' => 'Noções de Direito', 'sigla' => 'ND'],
        ['id' => 'bd', 'name' => 'Base de Dados', 'sigla' => 'BD'],
        ['id' => 'redes', 'name' => 'Redes de Computadores', 'sigla' => 'RC'],
        ['id' => 'tic', 'name' => 'Tecnologias de Informação e Comunicação', 'sigla' => 'TIC'],
        ['id' => 'mat', 'name' => 'Matemática', 'sigla' => 'MAT'],
        ['id' => 'sitinfo', 'name' => 'Sistemas de Informação', 'sigla' => 'SI'],
        ['id' => 'tlp', 'name' => 'Técnicas e Linguagem de Programação', 'sigla' => 'TLP'],
        ['id' => 'bdrc', 'name' => 'Base de Dados e Redes de Computadores', 'sigla' => 'BDRC'],
        ['id' => 'iag', 'name' => 'Informática Aplicada à Gestão', 'sigla' => 'IAG'],
        ['id' => 'emp', 'name' => 'Empreendedorismo', 'sigla' => 'EMPREEN'],
        ['id' => 'pt', 'name' => 'Projecto Tecnológico', 'sigla' => 'PJ.TEC'],
        ['id' => 'pap', 'name' => 'Prova de Aptidão Profissional', 'sigla' => 'PAP'],
    ];
    $grades = array_fill_keys(array_column($disciplines, 'id'), []);
    foreach (['lp', 'ing', 'fai', 'oae', 'imei', 'direito', 'bd', 'redes', 'tic'] as $index => $key) {
        $grades[$key] = ['mf' => 14 + $index, 'faltas' => 0];
    }
    $grades['mat'] = ['mt1' => 15, 'mt2' => 16, 'mt3' => 17, 'mf' => 16, 'faltas' => 2];
    $grades['sitinfo'] = ['mt1' => 12, 'mt2' => 13, 'mt3' => 14, 'mf' => 13, 'faltas' => 1];
    $grades['tlp'] = ['mt1' => 13, 'mt2' => 14, 'mt3' => 15, 'mf' => 14, 'faltas' => 0];
    $grades['bdrc'] = ['mt1' => 14, 'mt2' => 15, 'mt3' => 16, 'mf' => 15, 'faltas' => 1];
    $grades['iag'] = ['mt1' => 11, 'mt2' => 12, 'mt3' => 13, 'mf' => 12, 'faltas' => 0];
    $grades['emp'] = ['mt1' => 17, 'mt2' => 18, 'mt3' => 19, 'mf' => 18, 'faltas' => 0];
    $grades['pt'] = ['mt1' => 15, 'mt2' => 16, 'mt3' => 17, 'mf' => 16, 'faltas' => 0];
    $grades['pap'] = ['mf' => 18];
    $historico = [
        'lp-12' => 13,
        'ing-12' => 14,
        'fai-12' => 15,
        'oae-12' => 16,
        'imei-12' => 17,
        'direito-12' => 12,
        'bd-12' => 13,
        'redes-12' => 14,
        'tic-12' => 15,
        'mat-10' => 10,
        'mat-11' => 11,
        'mat-12' => 12,
        'tlp-10' => 9,
        'tlp-11' => 10,
        'tlp-12' => 11,
        'bdrc-12' => 12,
        'iag-12' => 13,
    ];

    foreach (['tarde', 'noite'] as $turno) {
        $templatePath = resource_path("templates/pautas/classes/13-classe/turnos/{$turno}/final.xlsx");
        $templateHash = hash_file('sha256', $templatePath);
        $contents = Excel::raw(new PautaFinalTemplateExcelExport(
            templatePath: $templatePath,
            header: [
                'institution' => 'Instituto de Teste',
                'class' => '13.ª Classe',
                'turma' => 'BNI',
                'area' => 'Informática',
                'course' => 'Técnico de Informática de Gestão',
                'year' => '2025/2026',
                'shift' => $turno,
                'room' => '19',
                'director' => 'Director de Teste',
                'class_coordinator' => null,
                'course_coordinator' => 'Coordenador de Teste',
                'subdirector' => 'Subdirector de Teste',
            ],
            disciplines: $disciplines,
            students: [
                [
                    'number' => '00213',
                    'name' => 'Alice Teste',
                    'grades' => $grades,
                    'previous_grades' => $historico,
                    'result' => 'transita',
                    'disciplines_recurso' => [],
                ],
                [
                    'number' => '00214',
                    'name' => 'Bruno Teste',
                    'grades' => [],
                    'previous_grades' => [],
                    'result' => 'incompleto',
                    'disciplines_recurso' => [],
                ],
            ],
        ), ExcelFormat::XLSX);

        $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_13_');
        file_put_contents($outputPath, $contents);

        try {
            $sheet = IOFactory::load($outputPath)->getSheetByName('P.FINAL');

            expect($sheet)->not->toBeNull()
                ->and($sheet->getCell('A13')->getValue())->toBe(1)
                ->and($sheet->getCell('B13')->getValue())->toBe('00213')
                ->and($sheet->getCell('C13')->getValue())->toBe('Alice Teste')
                ->and($sheet->getCell('D13')->getValue())->toBe(13.0)
                ->and($sheet->getCell('M13')->getValue())->toBe(10.0)
                ->and($sheet->getCell('N13')->getValue())->toBe(11.0)
                ->and($sheet->getCell('O13')->getValue())->toBe(12.0)
                ->and($sheet->getCell('Q13')->getValue())->toBe(16.0)
                ->and($sheet->getCell('T13')->getValue())->toBe('=IF(Q13="EEF","EEF",AVERAGE(M13,N13,O13,Q13))')
                ->and($sheet->getCell('Z13')->getValue())->toBe(9.0)
                ->and($sheet->getCell('AA13')->getValue())->toBe(10.0)
                ->and($sheet->getCell('AB13')->getValue())->toBe(11.0)
                ->and($sheet->getCell('AD13')->getValue())->toBe(14.0)
                ->and($sheet->getCell('AF13')->getValue())->toBe('=IF(AD13="EEF","EEF",AVERAGE(Z13,AA13,AB13,AD13))')
                ->and($sheet->getCell('AG13')->getValue())->toBe(12.0)
                ->and($sheet->getCell('AI13')->getValue())->toBe(15.0)
                ->and($sheet->getCell('AL13')->getValue())->toBe('=IF(AI13="EEF","EEF",AVERAGE(AG13,AI13))')
                ->and($sheet->getCell('AM13')->getValue())->toBe(13.0)
                ->and($sheet->getCell('AO13')->getValue())->toBe(12.0)
                ->and($sheet->getCell('AR13')->getValue())->toBe('=IF(AO13="EEF","EEF",AVERAGE(AM13,AO13))')
                ->and($sheet->getCell('AT13')->getValue())->toBe(18.0)
                ->and($sheet->getCell('AV13')->getValue())->toBe('=IF(AT13="EEF","EEF",AVERAGE(AT13))')
                ->and($sheet->getCell('AX13')->getValue())->toBe(16.0)
                ->and($sheet->getCell('AZ13')->getValue())->toBe('=IF(AX13="EEF","EEF",AVERAGE(AX13))')
                ->and($sheet->getCell('BA13')->getValue())->toBe(18.0)
                ->and($sheet->getCell('BB13')->getValue())->toBe('APTO')
                ->and($sheet->getCell('BC13')->getValue())->toBe('=SUM(T13,Y13,AF13,AL13,AR13,AV13,AZ13,BA13)/8')
                ->and($sheet->getCell('B14')->getValue())->toBe('00214')
                ->and($sheet->getCell('C14')->getValue())->toBe('Bruno Teste')
                ->and(in_array($sheet->getCell('BB14')->getValue(), [null, ''], true))->toBeTrue()
                ->and($sheet->getCell('A15')->getValue())->toBeNull()
                ->and($sheet->getMergeCells())->toContain('A15:C15', 'D15:L15')
                ->and($sheet->getCell('C3')->getValue())->toBe('Director de Teste')
                ->and($sheet->getCell('H4')->getValue())->toBe('INSTITUTO DE TESTE')
                ->and($sheet->getCell('P20')->getValue())->toBe('Coordenador de Teste')
                ->and($sheet->getCell('C20')->getValue())->toBeNull()
                ->and($sheet->getCell('AM20')->getValue())->toBe('Subdirector de Teste')
                ->and($sheet->getPageSetup()->getPrintArea())->toBe('A1:BC20');
        } finally {
            unlink($outputPath);
        }

        expect(hash_file('sha256', $templatePath))->toBe($templateHash);
    }
});

test('maps the 13th-grade template discipline labels to supported keys', function (): void {
    $mapper = new PautaFinalDisciplineMapper;

    expect($mapper->resolve('PORT.-CFD', null))->toBe('lp')
        ->and($mapper->resolve('ING/FRA.-CFD', null))->toBe('ing')
        ->and($mapper->resolve('MATEMAT 2', null))->toBe('mat2')
        ->and($mapper->resolve('SISTEMA', null))->toBe('sitinfo')
        ->and($mapper->resolve('B D R C', null))->toBe('bdrc')
        ->and($mapper->resolve('PAP', null))->toBe('pap');
});

test('maps real 12th-grade subject names to final-template keys', function (): void {
    $mapper = new PautaFinalDisciplineMapper;

    expect($mapper->resolve('MAT', 'Matemática'))->toBe('mat')
        ->and($mapper->resolve('EMPREEN', 'Empreendedorismo'))->toBe('emp')
        ->and($mapper->resolve('SI', 'Sistemas de Informação'))->toBe('sitinfo')
        ->and($mapper->resolve('L.Portuguesa', 'Lingua Portuguesa'))->toBe('lp')
        ->and($mapper->resolve('BD', 'Base de Dados'))->toBe('bd');
});

test('resolves the 10th-grade night templates for both exports', function (): void {
    $classe = new Classe;
    $classe->nome = '10ª Classe';

    $cursoClasse = new CursoClasse;
    $cursoClasse->setRelation('classe', $classe);

    $turno = new Turno;
    $turno->nome = 'Noite';

    $cursoClasseTurno = new CursoClasseTurno;
    $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
    $cursoClasseTurno->setRelation('turno', $turno);

    $turma = new Turma;
    $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);

    $resolver = new ResolvePautaTemplatePath;

    expect($resolver->resolveTrimestral($turma))
        ->toBe(resource_path('templates/pautas/classes/10-classe/turnos/noite/trimestral.xlsx'))
        ->and($resolver->resolveFinal($turma))
        ->toBe(resource_path('templates/pautas/classes/10-classe/turnos/noite/final.xlsx'));
});

test('resolves the 11th-grade night quarterly template', function (): void {
    $classe = new Classe;
    $classe->nome = '11.ª Classe';

    $cursoClasse = new CursoClasse;
    $cursoClasse->setRelation('classe', $classe);

    $turno = new Turno;
    $turno->nome = 'Noite';

    $cursoClasseTurno = new CursoClasseTurno;
    $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
    $cursoClasseTurno->setRelation('turno', $turno);

    $turma = new Turma;
    $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);

    expect((new ResolvePautaTemplatePath)->resolveTrimestral($turma))
        ->toBe(resource_path('templates/pautas/classes/11-classe/turnos/noite/trimestral.xlsx'));
});

test('resolves the 12th-grade afternoon final template for the test button', function (): void {
    $classe = new Classe;
    $classe->nome = '12.ª Classe';

    $cursoClasse = new CursoClasse;
    $cursoClasse->setRelation('classe', $classe);

    $turno = new Turno;
    $turno->nome = 'Tarde';

    $cursoClasseTurno = new CursoClasseTurno;
    $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
    $cursoClasseTurno->setRelation('turno', $turno);

    $turma = new Turma;
    $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);

    expect((new ResolvePautaTemplatePath)->resolveFinal($turma))
        ->toBe(resource_path('templates/pautas/classes/12-classe/turnos/tarde/final.xlsx'));
});

test('resolves final and quarterly template paths for every supported class and shift', function (): void {
    $resolver = new ResolvePautaTemplatePath;

    foreach ([10, 11, 12, 13] as $classNumber) {
        foreach (['Tarde' => 'tarde', 'Noite' => 'noite'] as $shiftName => $shiftSlug) {
            $classe = new Classe;
            $classe->nome = "{$classNumber}.ª Classe";

            $cursoClasse = new CursoClasse;
            $cursoClasse->setRelation('classe', $classe);

            $turno = new Turno;
            $turno->nome = $shiftName;

            $cursoClasseTurno = new CursoClasseTurno;
            $cursoClasseTurno->setRelation('cursoClasse', $cursoClasse);
            $cursoClasseTurno->setRelation('turno', $turno);

            $turma = new Turma;
            $turma->setRelation('cursoClasseTurno', $cursoClasseTurno);

            expect($resolver->resolveFinal($turma))
                ->toBe(resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shiftSlug}/final.xlsx"))
                ->and($resolver->resolveTrimestral($turma))
                ->toBe(resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shiftSlug}/trimestral.xlsx"));
        }
    }
});

test('fills the final template by matching the template subject labels', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/final.xlsx');
    $disciplines = [
        ['id' => 'tic', 'name' => 'Tecnologias de Informação e Comunicação', 'sigla' => 'TIC'],
        ['id' => 'bd', 'name' => 'Base de Dados', 'sigla' => 'BD'],
        ['id' => 'english', 'name' => 'Inglês', 'sigla' => 'L.Inglesa'],
        ['id' => 'fai', 'name' => 'Formação de Atitudes Integradoras', 'sigla' => 'FAI'],
        ['id' => 'math', 'name' => 'Matemática', 'sigla' => 'MAT'],
        ['id' => 'oae', 'name' => 'Organização e Administração de Empresas', 'sigla' => 'OAE'],
        ['id' => 'physical', 'name' => 'Educação Física', 'sigla' => 'Ed.Fisica'],
        ['id' => 'tlp', 'name' => 'Técnicas e Linguagem de programação', 'sigla' => 'TLP'],
        ['id' => 'portuguese', 'name' => 'Lingua Portuguesa', 'sigla' => 'L.Portuguesa'],
    ];
    $grades = [];
    foreach ($disciplines as $index => $discipline) {
        $grades[$discipline['id']] = [
            'mt1' => 10 + $index,
            'mt2' => 11 + $index,
            'mt3' => 12 + $index,
            'mf' => 11 + $index,
            'faltas' => $index,
        ];
    }

    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [
            'institution' => 'Instituto de Teste',
            'class' => '10ª Classe',
            'area' => 'Informática',
            'course' => 'Técnico de Informática de Gestão',
            'year' => '2026/2027',
            'room' => '5',
            'director' => 'Director de Teste',
        ],
        disciplines: $disciplines,
        students: [[
            'number' => 1,
            'name' => 'Aluno de Teste',
            'grades' => $grades,
            'result' => 'APTO',
        ], [
            'number' => 2,
            'name' => 'Zuleica de Teste',
            'grades' => array_fill_keys(array_column($disciplines, 'id'), []),
            'result' => 'recurso',
            'disciplines_recurso' => array_column($disciplines, 'id'),
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('C12')->getFormattedValue())->toBe('L.PORTUGUESA')
            ->and($sheet->getCell('H12')->getFormattedValue())->toBe('L.INGLESA')
            ->and($sheet->getCell('M12')->getValue())->toBe('MATEMÁTICA')
            ->and($sheet->getMergeCells())->toContain('M12:Q12', 'AB12:AF12')
            ->and($sheet->getStyle('M12')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('M12')->getFont()->getSize())->toBe(12.0)
            ->and($sheet->getCell('AG12')->getFormattedValue())->toBe('B.DADOS')
            ->and($sheet->getMergeCells())->toContain('B11:AB11')
            ->and($sheet->getStyle('B11')->getAlignment()->getWrapText())->toBeFalse()
            ->and($sheet->getCell('C15')->getValue())->toBe(18)
            ->and($sheet->getCell('A15')->getValue())->toBe(1)
            ->and($sheet->getStyle('A15')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('A15')->getFont()->getBold())->toBeFalse()
            ->and($sheet->getStyle('A15')->getFont()->getSize())->toBe(11.0)
            ->and($sheet->getCell('F15')->getValue())->toBe(19)
            ->and($sheet->getCell('AJ15')->getValue())->toBe(12)
            ->and($sheet->getCell('AK15')->getValue())->toBe(1)
            ->and($sheet->getCell('AV15')->getValue())->toBe('APTO')
            ->and($sheet->getMergeCells())->toContain('AV15:AW15')
            ->and(count($sheet->getDrawingCollection()))->toBe(1)
            ->and($sheet->getDrawingCollection()[0]->getCoordinates())->toBe('V1')
            ->and($sheet->getCell('AV16')->getValue())->toBe('REC. TIC, BD, L.INGLESA, FAI, MAT, OAE, ED.FISICA, TLP, L.PORTUGUESA')
            ->and($sheet->getColumnDimension('AW')->getWidth())->toBeGreaterThan(20.56)
            ->and($sheet->getStyle('AV16')->getAlignment()->getWrapText())->toBeFalse()
            ->and($sheet->getStyle('AV16')->getAlignment()->getShrinkToFit())->toBeTrue()
            ->and($sheet->getCell('B17')->getValue())->toBe('E.EF - Excluido por Excesso de Faltas')
            ->and($sheet->getCell('A17')->getValue())->toBeNull()
            ->and($sheet->getStyle('B17')->getAlignment()->getHorizontal())->toBe('left')
            ->and($sheet->getCell('B19')->getValue())->toBe('a) - Disciplina Feita')
            ->and($sheet->getCell('AX15')->getValue())->toBe('=SUM(F15,K15,P15,U15,Z15,AE15,AJ15,AO15,AT15)/9');
    } finally {
        unlink($outputPath);
    }
});

test('shrinks the final template and calculates honor roll using the actual discipline count', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/final.xlsx');
    $disciplines = [
        ['id' => 'bd', 'name' => 'Base de Dados', 'sigla' => 'BD'],
        ['id' => 'math', 'name' => 'Matemática', 'sigla' => 'MAT'],
    ];
    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [],
        disciplines: $disciplines,
        students: [[
            'name' => 'Aluno Teste',
            'grades' => [
                'bd' => ['mt1' => 10, 'mt2' => 12, 'mt3' => 14, 'mf' => 12, 'faltas' => 1],
                'math' => ['mt1' => 8, 'mt2' => 10, 'mt3' => 12, 'mf' => 10, 'faltas' => 2],
            ],
            'result' => 'recurso',
            'disciplines_recurso' => ['bd'],
        ]],
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('C12')->getFormattedValue())->toBe('MATEMÁTICA')
            ->and($sheet->getCell('H12')->getFormattedValue())->toBe('B.DADOS')
            ->and($sheet->getCell('M13')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getCell('O15')->getValue())->toBe('=SUM(F15,K15)/2')
            ->and($sheet->getCell('F15')->getValue())->toBe(10)
            ->and($sheet->getCell('K15')->getValue())->toBe(12)
            ->and($sheet->getMergeCells())->toContain('M15:N15')
            ->and($sheet->getCell('M15')->getValue())->toBe('REC. BD')
            ->and($sheet->getStyle('M15')->getFont()->getColor()->getARGB())->toBe('FFFF0000')
            ->and($sheet->getCell('C15')->getValue())->toBe(8)
            ->and($sheet->getStyle('C15')->getFont()->getColor()->getARGB())->toBe('FFFF0000');
    } finally {
        unlink($outputPath);
    }
});

test('adds a final-template discipline block beyond the nine template subjects', function (): void {
    $templatePath = resource_path('templates/pautas/classes/10-classe/turnos/tarde/final.xlsx');
    $disciplines = [
        ['id' => 'tic', 'name' => 'Tecnologias de Informação e Comunicação', 'sigla' => 'TIC'],
        ['id' => 'bd', 'name' => 'Base de Dados', 'sigla' => 'BD'],
        ['id' => 'english', 'name' => 'Inglês', 'sigla' => 'L.Inglesa'],
        ['id' => 'fai', 'name' => 'Formação de Atitudes Integradoras', 'sigla' => 'FAI'],
        ['id' => 'math', 'name' => 'Matemática', 'sigla' => 'MAT'],
        ['id' => 'oae', 'name' => 'Organização e Administração de Empresas', 'sigla' => 'OAE'],
        ['id' => 'physical', 'name' => 'Educação Física', 'sigla' => 'Ed.Fisica'],
        ['id' => 'tlp', 'name' => 'Técnicas e Linguagem de programação', 'sigla' => 'TLP'],
        ['id' => 'portuguese', 'name' => 'Lingua Portuguesa', 'sigla' => 'L.Portuguesa'],
        ['id' => 'extra', 'name' => 'Disciplina Extra', 'sigla' => 'EXTRA'],
    ];
    $students = [[
        'name' => 'Aluno Teste',
        'grades' => array_fill_keys(array_column($disciplines, 'id'), [
            'mt1' => 10,
            'mt2' => 10,
            'mt3' => 10,
            'mf' => 10,
            'faltas' => 0,
        ]),
        'result' => 'transita',
        'disciplines_recurso' => [],
    ]];
    $contents = Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: $templatePath,
        header: [],
        disciplines: $disciplines,
        students: $students,
    ), ExcelFormat::XLSX);

    $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_template_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getSheetByName('FINAL');

        expect($sheet)->not->toBeNull()
            ->and($sheet->getCell('AV12')->getValue())->toBe('EXTRA')
            ->and($sheet->getStyle('AV12')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('AV12')->getFont()->getSize())->toBe(12.0)
            ->and($sheet->getMergeCells())->toContain('AV12:AZ12')
            ->and($sheet->getCell('BA13')->getFormattedValue())->toBe('RESULTADO')
            ->and($sheet->getCell('BC15')->getValue())->toBe('=SUM(F15,K15,P15,U15,Z15,AE15,AJ15,AO15,AT15,AY15)/10');
    } finally {
        unlink($outputPath);
    }
});

test('generates final exports from every real class and shift template', function (): void {
    foreach ([10, 11, 12, 13] as $classNumber) {
        foreach (['tarde', 'noite'] as $shift) {
            $templatePath = resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shift}/final.xlsx");
            $row = match ($classNumber) {
                12 => 21,
                13 => 13,
                default => 15,
            };
            $lastRow = $row + 39;
            $discipline = ['id' => 'mat', 'name' => 'Matemática', 'sigla' => 'MAT'];
            $students = collect(range(1, 40))
                ->map(fn (int $index): array => [
                    'number' => 20260000 + $index,
                    'name' => sprintf('Áluno Nº %02d - São Tomé', $index),
                    'grades' => ['mat' => ['mt1' => 12, 'mt2' => 13, 'mt3' => 14, 'mf' => 13, 'faltas' => 0]],
                    'previous_grades' => ['mat' => 11, 'mat-11' => 12],
                    'result' => 'transita',
                    'disciplines_recurso' => [],
                ])
                ->all();

            $contents = Excel::raw(new PautaFinalTemplateExcelExport(
                templatePath: $templatePath,
                header: ['class' => "{$classNumber}.ª Classe", 'year' => '2026/2027', 'room' => '09'],
                disciplines: [$discipline],
                students: $students,
            ), ExcelFormat::XLSX);

            $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_matrix_');
            file_put_contents($outputPath, $contents);

            try {
                $sheet = IOFactory::load($outputPath)->getSheetByName($classNumber === 10 ? 'FINAL' : 'P.FINAL');

                expect($sheet)->not->toBeNull()
                    ->and($sheet->getCell("A{$row}")->getValue())->toBe(1)
                    ->and($sheet->getCell("A{$lastRow}")->getValue())->toBe(40);

                if ($classNumber >= 12) {
                    expect($sheet->getCell("B{$row}")->getValue())->toBe(20260001)
                        ->and($sheet->getCell("C{$row}")->getValue())->toBe('Áluno Nº 01 - São Tomé')
                        ->and($sheet->getCell("B{$lastRow}")->getValue())->toBe(20260040)
                        ->and($sheet->getCell("C{$lastRow}")->getValue())->toBe('Áluno Nº 40 - São Tomé');
                } else {
                    expect($sheet->getCell("B{$row}")->getValue())->toBe('Áluno Nº 01 - São Tomé')
                        ->and($sheet->getCell("B{$lastRow}")->getValue())->toBe('Áluno Nº 40 - São Tomé');
                }
            } finally {
                unlink($outputPath);
            }
        }
    }
});

test('generates empty final rosters from every real class and shift template', function (): void {
    foreach ([10, 11, 12, 13] as $classNumber) {
        foreach (['tarde', 'noite'] as $shift) {
            $templatePath = resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shift}/final.xlsx");
            $contents = Excel::raw(new PautaFinalTemplateExcelExport(
                templatePath: $templatePath,
                header: [],
                disciplines: [['id' => 'mat', 'name' => 'Matemática', 'sigla' => 'MAT']],
                students: [],
            ), ExcelFormat::XLSX);

            $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_empty_');
            file_put_contents($outputPath, $contents);

            try {
                $sheet = IOFactory::load($outputPath)->getSheetByName($classNumber === 10 ? 'FINAL' : 'P.FINAL');
                $firstStudentRow = match ($classNumber) {
                    12 => 21,
                    13 => 13,
                    default => 15,
                };

                expect($sheet)->not->toBeNull()
                    ->and($sheet->getCell("A{$firstStudentRow}")->getValue())->toBeNull();
            } finally {
                unlink($outputPath);
            }
        }
    }
});

test('leaves incomplete final results blank for every class and shift', function (): void {
    foreach ([10, 11, 12, 13] as $classNumber) {
        foreach (['tarde', 'noite'] as $shift) {
            $templatePath = resource_path("templates/pautas/classes/{$classNumber}-classe/turnos/{$shift}/final.xlsx");
            $contents = Excel::raw(new PautaFinalTemplateExcelExport(
                templatePath: $templatePath,
                header: [],
                disciplines: [['id' => 'mat', 'name' => 'Matemática', 'sigla' => 'MAT']],
                students: [[
                    'name' => 'Aluno Incompleto',
                    'grades' => [],
                    'previous_grades' => [],
                    'result' => 'incompleto',
                    'disciplines_recurso' => [],
                ]],
            ), ExcelFormat::XLSX);

            $outputPath = tempnam(sys_get_temp_dir(), 'pauta_final_incompleto_');
            file_put_contents($outputPath, $contents);

            try {
                $sheet = IOFactory::load($outputPath)->getSheetByName($classNumber === 10 ? 'FINAL' : 'P.FINAL');
                $linhaAluno = match ($classNumber) {
                    12 => 21,
                    13 => 13,
                    default => 15,
                };
                $colunaResultado = match ($classNumber) {
                    12 => 'BL',
                    13 => 'BB',
                    default => null,
                };

                if ($colunaResultado === null) {
                    for ($coluna = 1; $coluna <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $coluna++) {
                        $letra = Coordinate::stringFromColumnIndex($coluna);
                        if (mb_strtoupper(trim((string) $sheet->getCell($letra.'13')->getValue()), 'UTF-8') === 'RESULTADO') {
                            $colunaResultado = $letra;

                            break;
                        }
                    }
                }

                expect($colunaResultado)->not->toBeNull()
                    ->and(in_array($sheet->getCell($colunaResultado.$linhaAluno)->getValue(), [null, ''], true))->toBeTrue();
            } finally {
                unlink($outputPath);
            }
        }
    }
});

test('rejects final exports without disciplines', function (): void {
    expect(fn () => Excel::raw(new PautaFinalTemplateExcelExport(
        templatePath: resource_path('templates/pautas/classes/10-classe/turnos/tarde/final.xlsx'),
        header: [],
        disciplines: [],
        students: [],
    ), ExcelFormat::XLSX))->toThrow(InvalidArgumentException::class);
});
