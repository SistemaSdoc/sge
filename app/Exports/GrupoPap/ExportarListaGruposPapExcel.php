<?php

namespace App\Exports\GrupoPap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithExportTemplate;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportarListaGruposPapExcel implements Export, FromArray, WithEvents, WithExportTemplate
{
    /**
     * @param  Collection<int, GrupoPap>  $groups
     */
    public function __construct(
        private readonly Collection $groups,
        private readonly string $institutionName,
        private readonly string $courseName,
        private readonly string $className,
        private readonly string $schoolYearName,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function exportTemplate(): string
    {
        return resource_path('templates/lista-grupos-pap/template.xlsx');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->fillSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function fillSheet(Worksheet $sheet): void
    {
        $courseName = mb_strtoupper($this->courseName, 'UTF-8');
        $institutionName = mb_strtoupper($this->institutionName, 'UTF-8');

        $this->setText($sheet, 'A6', $institutionName);
        $this->setText($sheet, 'A7', "COORDENAÇÃO DO CURSO DE {$courseName}");
        $this->setText($sheet, 'A8', "COORDENAÇÃO DO GRUPO DISCIPLINAR DE {$courseName}");
        $this->setText(
            $sheet,
            'A9',
            "DADOS DOS GRUPOS DE PRÁTICAS DE APTIDÃO PROFISSIONAL (PAP), {$this->className} CLASSE {$this->schoolYearName}",
        );

        $groups = $this->groups->values();
        $studentCounts = $groups->map(
            fn (GrupoPap $group): int => max(
                1,
                $group->elementos
                    ->map(fn ($elementoGrupoPap) => $elementoGrupoPap->aluno)
                    ->filter()
                    ->count(),
            ),
        );
        $outputRows = $studentCounts->sum(fn (int $studentCount): int => 5 + $studentCount);
        $templateLastRow = $sheet->getHighestDataRow();
        $templateRowCount = max(0, $templateLastRow - 10);

        if ($outputRows > 0) {
            $sheet->insertNewRowBefore(11, $outputRows);
        }

        $templateOffset = $outputRows;
        $outputRow = 11;
        $lastDataRow = 9;

        foreach ($groups as $groupIndex => $group) {
            $studentCount = $studentCounts[$groupIndex];

            foreach ([11, 12, 13, 14] as $headerOffset => $sourceRow) {
                $this->copyRowStyle($sheet, $sourceRow + $templateOffset, $outputRow + $headerOffset);
            }

            $studentStartRow = $outputRow + 4;
            $students = $group->elementos
                ->map(fn ($elementoGrupoPap) => $elementoGrupoPap->aluno)
                ->filter()
                ->values();

            foreach (range(0, $studentCount - 1) as $studentIndex) {
                $studentRow = $studentStartRow + $studentIndex;
                $styleSourceRow = $studentIndex === $studentCount - 1 ? 18 : 15;
                $this->copyRowStyle($sheet, $styleSourceRow + $templateOffset, $studentRow);
            }

            $spacerRow = $studentStartRow + $studentCount;
            $this->copyRowStyle($sheet, 19 + $templateOffset, $spacerRow);

            $macroNumber = $groupIndex + 1;
            $topic = filled($group->tema_grupo) ? $group->tema_grupo : 'Tema não definido, ainda.';
            $caseStudy = filled($group->estudo_caso)
                ? "Caso de estudo  {$group->estudo_caso}"
                : 'Caso de estudo: não definido, ainda.';
            $this->setText($sheet, "A{$outputRow}", str_pad((string) $macroNumber, 2, '0', STR_PAD_LEFT));
            $this->setText($sheet, "B{$outputRow}", $topic);
            $this->setText($sheet, 'B'.($outputRow + 1), $caseStudy);
            $this->setText($sheet, 'B'.($outputRow + 2), $this->tutorInfo($group));

            $headersRow = $outputRow + 3;
            foreach ([
                'B' => 'Nome Completo',
                'C' => 'Nº BI',
                'D' => 'Nº Estudante',
                'E' => 'Telefone',
                'F' => 'Email',
            ] as $column => $label) {
                $this->setText($sheet, "{$column}{$headersRow}", $label);
            }

            foreach ($students as $studentIndex => $aluno) {
                $row = $studentStartRow + $studentIndex;
                $candidate = $aluno->inscricao?->candidato;
                $studentUser = $aluno->user;

                foreach ([
                    'B' => $candidate?->nome ?? '',
                    'C' => $candidate?->bi ?? $studentUser?->bi ?? '',
                    'D' => $candidate?->numero_estudante ?? $aluno->numero_processo ?? '',
                    'E' => $candidate?->telefone ?? $studentUser?->telefone ?? '',
                    'F' => $candidate?->email ?? $studentUser?->email ?? '',
                ] as $column => $value) {
                    $this->setText($sheet, "{$column}{$row}", (string) $value);
                }
            }

            $lastDataRow = $studentStartRow + $studentCount - 1;
            $outputRow = $spacerRow + 1;
        }

        if ($templateRowCount > 0) {
            $sheet->removeRow(11 + $templateOffset, $templateRowCount);
        }

        $sheet->getPageSetup()->setPrintArea("A1:F{$lastDataRow}");
    }

    private function tutorInfo(GrupoPap $group): string
    {
        $tutor = $group->professor?->user;
        $tutorDetails = array_filter([
            filled($tutor?->nome) ? "Prof. {$tutor->nome}" : null,
            $tutor?->email,
            $tutor?->telefone,
        ]);
        $tutorDetails = $tutorDetails === []
            ? 'Não definido'
            : implode('   ·   ', $tutorDetails);
        $turmaName = $group->turma?->nome ?: 'não definida';

        return "Tutor:  {$tutorDetails}   ·   Turma  {$turmaName}";
    }

    private function copyRowStyle(Worksheet $sheet, int $sourceRow, int $targetRow): void
    {
        $sheet->duplicateStyle($sheet->getStyle("A{$sourceRow}:F{$sourceRow}"), "A{$targetRow}:F{$targetRow}");

        $rowHeight = $sheet->getRowDimension($sourceRow)->getRowHeight();

        if ($rowHeight > 0) {
            $sheet->getRowDimension($targetRow)->setRowHeight($rowHeight);
        }
    }

    private function setText(Worksheet $sheet, string $cell, string $value): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }
}
