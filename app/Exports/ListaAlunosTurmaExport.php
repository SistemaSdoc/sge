<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

class ListaAlunosTurmaExport
{
    /**
     * @param  array{instituicao: string, director: string, curso: string, ano_lectivo: string, classe: string, turma: string, sala: string}  $cabecalho
     * @param  list<array{numero: int, nome: string, processo: string, idade: int|string, sexo: string, contacto: string}>  $alunos
     */
    public function gerar(array $cabecalho, array $alunos): string
    {
        $spreadsheet = IOFactory::load(resource_path('templates/Lista_alunos_turma.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A4', mb_strtoupper($cabecalho['instituicao'], 'UTF-8'));
        $sheet->setCellValue('A7', 'VISTO DO DIRECTOR');
        $sheet->setCellValue('A8', $cabecalho['director'] !== '' ? $cabecalho['director'] : '________________________');
        $sheet->setCellValue('A12', mb_strtoupper($cabecalho['curso'], 'UTF-8'));
        $sheet->setCellValue(
            'A13',
            $cabecalho['classe'].' CLASSE ; TURMA: '.$cabecalho['turma'].' ; SALA: '.($cabecalho['sala'] !== '' ? $cabecalho['sala'] : '—')
        );
        $sheet->setCellValue('C13', 'ANO LECTIVO: '.$cabecalho['ano_lectivo']);

        $sheet->getStyle('A7:C7')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
        ]);
        $sheet->getStyle('A8:C8')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
        ]);
        $sheet->getStyle('A12:G12')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 14, 'bold' => true],
        ]);
        $sheet->getStyle('A13:G13')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
        ]);
        $sheet->getStyle('A15:G15')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
        ]);

        $drawing = $sheet->getDrawingCollection()[0] ?? null;
        if ($drawing instanceof Drawing) {
            $drawing->setPath(public_path('images/insignia_angola.png'));
        } else {
            $drawing = new Drawing;
            $drawing->setName('Insígnia da República de Angola');
            $drawing->setDescription('Insígnia da República de Angola');
            $drawing->setPath(public_path('images/insignia_angola.png'));
            $drawing->setCoordinates('C1');
            $drawing->setHeight(70);
            $drawing->setWorksheet($sheet);
        }

        $firstDataRow = 16;
        $lastTemplateRow = 37;
        $templateCapacity = $lastTemplateRow - $firstDataRow + 1;
        $extraRows = max(0, count($alunos) - $templateCapacity);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore($lastTemplateRow, $extraRows);

            for ($row = $lastTemplateRow; $row < $lastTemplateRow + $extraRows; $row++) {
                $sheet->duplicateStyle($sheet->getStyle('A36:G36'), "A{$row}:G{$row}");
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension(36)->getRowHeight());
            }
        }

        foreach ($alunos as $index => $aluno) {
            $row = $firstDataRow + $index;
            $sheet->setCellValue("A{$row}", $aluno['numero']);
            $sheet->setCellValue("B{$row}", $aluno['nome']);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->setCellValueExplicit("C{$row}", $aluno['processo'], DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $aluno['idade']);
            $sheet->setCellValue("E{$row}", $aluno['sexo']);
            $sheet->setCellValueExplicit("F{$row}", $aluno['contacto'], DataType::TYPE_STRING);
        }

        $lastDataRow = max($lastTemplateRow, $firstDataRow + count($alunos) - 1);
        $sheet->getStyle("A{$firstDataRow}:G{$lastDataRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        ]);
        $sheet->getStyle("B{$firstDataRow}:B{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $printLastRow = max($lastTemplateRow, $firstDataRow + count($alunos) - 1);
        $sheet->getPageSetup()->setPrintArea("A1:G{$printLastRow}");

        $temporaryFile = tempnam(sys_get_temp_dir(), 'lista_alunos_');
        if ($temporaryFile === false) {
            throw new RuntimeException('Não foi possível criar o ficheiro temporário da lista.');
        }

        $outputPath = $temporaryFile.'.xlsx';
        if (! rename($temporaryFile, $outputPath)) {
            unlink($temporaryFile);
            throw new RuntimeException('Não foi possível preparar o ficheiro Excel da lista.');
        }

        try {
            (new Xlsx($spreadsheet))->save($outputPath);
        } catch (Throwable $exception) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }

            throw $exception;
        }

        return $outputPath;
    }
}
