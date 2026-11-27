<?php

namespace App\Exports\PautaFinal;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithExportTemplate;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta pautas finais preservando o modelo Excel de cada classe.
 */
class PautaFinalTemplateExcelExport implements Export, FromArray, WithEvents, WithExportTemplate
{
    /**
     * @param  array<string, string|null>  $header
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplines
     * @param  list<array<string, mixed>>  $students
     */
    public function __construct(
        private readonly string $templatePath,
        private readonly array $header,
        private readonly array $disciplines,
        private readonly array $students,
    ) {}

    /**
     * Mantém os dados vazios, pois o preenchimento ocorre depois de carregar o modelo.
     */
    public function array(): array
    {
        return [];
    }

    /**
     * Devolve o caminho do workbook usado como modelo.
     */
    public function exportTemplate(): string
    {
        return $this->templatePath;
    }

    /**
     * Regista o preenchimento da folha após o template ser carregado.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $this->preencherFolha($evento->sheet->getDelegate());
            },
        ];
    }

    /**
     * Entrega o preenchimento ao módulo dedicado de pautas finais.
     */
    private function preencherFolha(Worksheet $folha): void
    {
        (new PreenchedorPautaFinalTemplate(
            $this->templatePath,
            $this->header,
            $this->disciplines,
            $this->students
        ))->preencher($folha);
    }
}
