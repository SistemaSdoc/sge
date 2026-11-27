<?php

namespace App\Exports\MiniPauta;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithExportTemplate;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta as mini-pautas das disciplinas no workbook modelo.
 */
class MiniPautaExcelExport implements Export, FromArray, WithEvents, WithExportTemplate, WithMultipleSheets, WithTitle
{
    /**
     * @param  list<array{nome: string, sigla: string, alunos: array<int, array<string, mixed>>}>  $disciplinas
     * @param  array{nome: string, sigla: string, alunos: array<int, array<string, mixed>>}|null  $disciplina
     */
    public function __construct(
        private readonly array $disciplinas,
        private readonly string $curso,
        private readonly string $turma,
        private readonly string $anoLetivo,
        private readonly string $instituicao,
        private readonly string $sala,
        private readonly string $classe,
        private readonly ?array $disciplina = null,
    ) {}

    /**
     * Cria uma exportação de folha para cada disciplina do workbook.
     *
     * @return list<self>
     */
    public function sheets(): array
    {
        return array_map(
            fn (array $disciplina): self => new self(
                disciplinas: [],
                curso: $this->curso,
                turma: $this->turma,
                anoLetivo: $this->anoLetivo,
                instituicao: $this->instituicao,
                sala: $this->sala,
                classe: $this->classe,
                disciplina: $disciplina,
            ),
            $this->disciplinas,
        );
    }

    /**
     * Mantém os dados vazios porque o preenchimento acontece depois de carregar o template.
     */
    public function array(): array
    {
        return [];
    }

    /**
     * Devolve o workbook de mini-pauta usado como base da exportação.
     */
    public function exportTemplate(): string
    {
        return resource_path('templates/mini-pautas/mini-pauta-disciplinas.xlsx');
    }

    public function title(): string
    {
        return mb_strtoupper($this->disciplina['sigla'] ?? 'MINI-PAUTA');
    }

    public function registerEvents(): array
    {
        if ($this->disciplina !== null) {
            return [
                AfterSheet::class => function (AfterSheet $event): void {
                    $this->preencherFolha($event->sheet->getDelegate());
                },
            ];
        }

        return [
            BeforeExport::class => function (BeforeExport $event): void {
                $spreadsheet = $event->writer->getDelegate();
                $templateSheet = $spreadsheet->getSheet(0);
                $templateTitle = $templateSheet->getTitle();

                $sheetCount = count($spreadsheet->getAllSheets());
                for ($sheetIndex = $sheetCount - 1; $sheetIndex > 0; $sheetIndex--) {
                    $spreadsheet->removeSheetByIndex($sheetIndex);
                }

                $disciplinasCount = count($this->disciplinas);
                for ($index = 1; $index < $disciplinasCount; $index++) {
                    $spreadsheet->duplicateWorksheetByTitle($templateTitle);
                }
            },
        ];
    }

    private function preencherFolha(Worksheet $folha): void
    {
        if ($this->disciplina === null) {
            return;
        }

        (new PreenchedorMiniPautaTemplate(
            disciplinaNome: $this->disciplina['nome'],
            sigla: $this->disciplina['sigla'],
            alunos: $this->disciplina['alunos'],
            curso: $this->curso,
            turma: $this->turma,
            anoLetivo: $this->anoLetivo,
            instituicao: $this->instituicao,
            sala: $this->sala,
            classe: $this->classe,
        ))->preencher($folha);
    }
}
