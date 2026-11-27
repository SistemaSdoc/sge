<?php

use App\Exports\GrupoPap\ExportarListaGruposPapExcel;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

uses(TestCase::class);

it('fills the workbook template with dynamic group rows and removes its examples', function (): void {
    $groups = collect();

    $group = new GrupoPap;
    $group->setAttribute('tema_grupo', 'Tema de teste');
    $group->setAttribute('estudo_caso', 'Caso de teste');

    $tutorUser = new User;
    $tutorUser->setAttribute('nome', 'Tutor de Teste');
    $tutorUser->setAttribute('email', 'tutor@example.test');
    $tutorUser->setAttribute('telefone', '923000001');
    $professor = new Professor;
    $professor->setRelation('user', $tutorUser);
    $group->setRelation('professor', $professor);

    $turma = new Turma;
    $turma->setAttribute('nome', '13A');
    $group->setRelation('turma', $turma);

    $elements = [];
    foreach (range(1, 5) as $studentNumber) {
        $candidate = new Candidato;
        $candidate->setAttribute('nome', "Aluno {$studentNumber}");
        $candidate->setAttribute('bi', "BI000{$studentNumber}");
        $candidate->setAttribute('numero_estudante', "EST000{$studentNumber}");
        $candidate->setAttribute('telefone', $studentNumber === 1 ? '=1+1' : "92300000{$studentNumber}");
        $candidate->setAttribute('email', "aluno{$studentNumber}@example.test");

        $inscricao = new Inscricao;
        $inscricao->setRelation('candidato', $candidate);

        $aluno = new Aluno;
        $aluno->setRelation('inscricao', $inscricao);
        $aluno->setRelation('user', new User);

        $elemento = new ElementoGrupoPap;
        $elemento->setRelation('aluno', $aluno);
        $elements[] = $elemento;
    }

    $group->setRelation('elementos', new EloquentCollection($elements));
    $groups->push($group);

    $emptyGroup = new GrupoPap;
    $emptyGroup->setRelation('elementos', new EloquentCollection);
    $emptyGroup->setRelation('turma', $turma);
    $groups->push($emptyGroup);

    $contents = Excel::raw(new ExportarListaGruposPapExcel(
        $groups,
        'Instituto de Teste',
        'Informática de Gestão',
        '13ª',
        '2025/2026',
    ), ExcelFormat::XLSX);
    $outputPath = tempnam(sys_get_temp_dir(), 'grupos_pap_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getActiveSheet();

        expect($sheet->getCell('A6')->getValue())->toBe('INSTITUTO DE TESTE')
            ->and($sheet->getCell('A7')->getValue())->toBe('COORDENAÇÃO DO CURSO DE INFORMÁTICA DE GESTÃO')
            ->and($sheet->getCell('A9')->getValue())->toContain('13ª CLASSE 2025/2026')
            ->and($sheet->getCell('A11')->getValue())->toBe('01')
            ->and($sheet->getCell('B11')->getValue())->toBe('Tema de teste')
            ->and($sheet->getCell('B12')->getValue())->toBe('Caso de estudo  Caso de teste')
            ->and($sheet->getCell('B13')->getValue())->toContain('Tutor  Prof. Tutor de Teste')
            ->and($sheet->getCell('B14')->getValue())->toBe('Nome Completo')
            ->and($sheet->getCell('B15')->getValue())->toBe('Aluno 1')
            ->and($sheet->getCell('E15')->getValue())->toBe('=1+1')
            ->and($sheet->getCell('E15')->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('A19')->getStyle()->getBorders()->getBottom()->getBorderStyle())->toBe('thin')
            ->and($sheet->getCell('A21')->getValue())->toBe('02')
            ->and($sheet->getCell('B21')->getValue())->toBe('Tema não definido, ainda.')
            ->and($sheet->getCell('B22')->getValue())->toBe('Caso de estudo não definido, ainda.')
            ->and($sheet->getCell('B23')->getValue())->toBe('Tutor  não definido, ainda.   ·   Turma  13A')
            ->and($sheet->getCell('B48')->getValue())->toBeNull()
            ->and($sheet->getPageSetup()->getPrintArea())->toBe('A1:F25')
            ->and($sheet->getMergeCells())->toContain('A6:F6', 'A7:F7', 'A8:F8', 'A9:F9')
            ->and($sheet->getDrawingCollection())->toHaveCount(1);
    } finally {
        unlink($outputPath);
    }
});

it('keeps only the header when there are no groups to export', function (): void {
    $contents = Excel::raw(new ExportarListaGruposPapExcel(
        collect(),
        'Instituto de Teste',
        'Informática',
        '13ª',
        '2025/2026',
    ), ExcelFormat::XLSX);
    $outputPath = tempnam(sys_get_temp_dir(), 'grupos_pap_');
    file_put_contents($outputPath, $contents);

    try {
        $sheet = IOFactory::load($outputPath)->getActiveSheet();

        expect($sheet->getCell('A6')->getValue())->toBe('INSTITUTO DE TESTE')
            ->and($sheet->getCell('A11')->getValue())->toBeNull()
            ->and($sheet->getCell('B11')->getValue())->toBeNull()
            ->and($sheet->getPageSetup()->getPrintArea())->toBe('A1:F9');
    } finally {
        unlink($outputPath);
    }
});
