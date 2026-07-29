<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PautaFinalSheetExport implements FromArray, WithEvents, WithTitle
{
    const DATA_START_ROW = 8;

    const MAX_ALUNOS = 40;

    const COR_AZUL_TEXTO = '1411d9';

    const COR_VERDE_TEXTO = '1B5E20';

    const COR_VERM_TEXTO = 'B71C1C';

    const COR_AMBAR_TEXTO = '7B4F00';

    public function __construct(
        protected array $disciplinas,
        protected array $alunos,
        protected string $curso,
        protected string $turma,
        protected string $anoLetivo,
        protected string $instituicao,
        protected string $sala,
        protected string $classe,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'PAUTA FINAL';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $e) => $this->buildSheet($e->sheet->getDelegate()),
        ];
    }

    private function colLetter(int $idx): string
    {
        $letters = '';
        $idx++;
        while ($idx > 0) {
            $idx--;
            $letters = chr(65 + ($idx % 26)).$letters;
            $idx = intdiv($idx, 26);
        }

        return $letters;
    }

    private function discBaseIdx(int $d): int
    {
        return 2 + $d * 5;
    }

    private function discCols(int $d): array
    {
        $base = $this->discBaseIdx($d);

        return [
            $this->colLetter($base),
            $this->colLetter($base + 1),
            $this->colLetter($base + 2),
            $this->colLetter($base + 3),
            $this->colLetter($base + 4),
        ];
    }

    private function resultadoCol(): string
    {
        return $this->colLetter(2 + count($this->disciplinas) * 5);
    }

    private function lastCol(): string
    {
        return $this->resultadoCol();
    }

    protected function buildSheet(Worksheet $ws): void
    {
        $numDisc = count($this->disciplinas);
        $lastColLet = $this->lastCol();
        $resColLet = $this->resultadoCol();
        $thin = Border::BORDER_MEDIUM;
        $thin = Border::BORDER_THIN;

        // 1. LARGURAS
        $ws->getColumnDimension('A')->setWidth(4.5);
        $ws->getColumnDimension('B')->setWidth(38.0);
        for ($d = 0; $d < $numDisc; $d++) {
            [$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);
            $ws->getColumnDimension($c1T)->setWidth(5.5);
            $ws->getColumnDimension($c2T)->setWidth(5.5);
            $ws->getColumnDimension($c3T)->setWidth(5.5);
            $ws->getColumnDimension($cF)->setWidth(4.0);
            $ws->getColumnDimension($cMF)->setWidth(6.5);
        }
        $ws->getColumnDimension($resColLet)->setWidth(12.0);

        // 2. LINHA 2 — Instituição
        $ws->getRowDimension(2)->setRowHeight(18);
        $ws->mergeCells("A2:{$lastColLet}2");
        $ws->setCellValue('A2', mb_strtoupper($this->instituicao));
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 12, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 3. LINHAS 3-5 — Curso, Pauta Final (esquerda) + Classe/Turma/Ano (direita, LINHA 4 SÓ)
        foreach ([3 => 13.5, 4 => 13.5, 5 => 13.5] as $r => $h) {
            $ws->getRowDimension($r)->setRowHeight($h);
        }

        $ws->setCellValue('B3', 'CURSO: '.mb_strtoupper($this->curso));
        $ws->getStyle('B3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $ws->setCellValue('B4', 'PAUTA FINAL');
        $ws->getStyle('B4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Classe / Turma / Ano — LINHA 4 (coluna R, logo após NOME)
        $classeNome = is_array($this->classe) ? ($this->classe['nome'] ?? '') : $this->classe;
        $ws->setCellValue('R4', "CLASSE: {$classeNome}   TURMA: {$this->turma}   {$this->anoLetivo}");
        $ws->getStyle('R4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 4. CABEÇALHO DA TABELA — Linhas 6-7 (Nº/Nome merge 6-7 + Siglas linha 6 + Sub-colunas linha 7)
        $ws->getRowDimension(6)->setRowHeight(16.0);
        $ws->getRowDimension(7)->setRowHeight(14.0);

        // Nº e Nome (merge vertical 6-7)
        $ws->mergeCells('A6:A7');
        $ws->setCellValue('A6', 'Nº');
        $ws->mergeCells('B6:B7');
        $ws->setCellValue('B6', 'NOME');
        foreach (['A6', 'B6'] as $c) {
            $ws->getStyle($c)->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Disciplinas (siglas na linha 6, sub-colunas na linha 7)
        for ($d = 0; $d < $numDisc; $d++) {
            [$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);
            $sigla = mb_strtoupper($this->disciplinas[$d]['sigla'] ?? $this->disciplinas[$d]['nome']);

            // Sigla (merge horizontal das 5 sub-colunas)
            $ws->mergeCells("{$c1T}6:{$cMF}6");
            $ws->setCellValue("{$c1T}6", $sigla);
            $ws->getStyle("{$c1T}6")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['bottom' => ['borderStyle' => $thin]],
            ]);

            // Sub-colunas (1T, 2T, 3T, F, MF)
            foreach ([
                $c1T => '1T',
                $c2T => '2T',
                $c3T => '3T',
                $cF => 'F',
                $cMF => 'MF',
            ] as $col => $label) {
                $ws->setCellValue("{$col}7", $label);
                $ws->getStyle("{$col}7")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
        }

        // Resultado (merge vertical 6-7)
        $ws->mergeCells("{$resColLet}6:{$resColLet}7");
        $ws->setCellValue("{$resColLet}6", 'RESULTADO');
        $ws->getStyle("{$resColLet}6")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        // 5. BORDAS CABEÇALHO
        $ws->getStyle('A6:A7')->applyFromArray(['borders' => [
            'left' => ['borderStyle' => $thin],
            'right' => ['borderStyle' => $thin],
            'top' => ['borderStyle' => $thin],
            'bottom' => ['borderStyle' => $thin],
        ]]);
        $ws->getStyle('B6:B7')->applyFromArray(['borders' => [
            'top' => ['borderStyle' => $thin],
            'bottom' => ['borderStyle' => $thin],
            'left' => ['borderStyle' => $thin],
            'right' => ['borderStyle' => $thin],
        ]]);

        for ($d = 0; $d < $numDisc; $d++) {
            [$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);

            // Sigla (linha 6) — top medium, bottom medium
            $ws->getStyle("{$c1T}6:{$cMF}6")->applyFromArray(['borders' => [
                'top' => ['borderStyle' => $thin],
                'bottom' => ['borderStyle' => $thin],
            ]]);

            // Sub-colunas (linha 7) — thin em volta, bottom medium
            foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
                $ws->getStyle("{$col}7")->applyFromArray(['borders' => [
                    'top' => ['borderStyle' => $thin],
                    'bottom' => ['borderStyle' => $thin],
                    'left' => ['borderStyle' => $thin],
                    'right' => ['borderStyle' => $thin],
                ]]);
            }

            // Primeira sub-coluna (left medium), última (right medium)
            $ws->getStyle("{$c1T}7")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
            $ws->getStyle("{$cMF}7")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
        }

        // Resultado
        $ws->getStyle("{$resColLet}6:{$resColLet}7")->applyFromArray(['borders' => [
            'outline' => ['borderStyle' => $thin],
        ]]);

        // 6. DADOS DOS ALUNOS
        for ($i = 0; $i < count($this->alunos); $i++) {
            $row = self::DATA_START_ROW + $i;
            $aluno = $this->alunos[$i];
            $res = $aluno['resultado'] ?? '';

            $ws->getRowDimension($row)->setRowHeight(14.25);
            $ws->setCellValue("A{$row}", $aluno['numero'] ?? ($i + 1));
            $ws->setCellValue("B{$row}", $aluno['nome'] ?? '');

            $ws->getStyle("A{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $ws->getStyle("B{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            for ($d = 0; $d < $numDisc; $d++) {
                $discNome = $this->disciplinas[$d]['nome'];
                $nota = $aluno['notas'][$discNome] ?? null;
                [$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);

                $mt1 = $nota['mt1'] ?? null;
                $mt2 = $nota['mt2'] ?? null;
                $mt3 = $nota['mt3'] ?? null;
                $f = isset($nota['faltas']) ? (int) $nota['faltas'] : null;
                $mf = $nota['media_final'] ?? null;

                if ($mt1 !== null) {
                    $ws->setCellValue("{$c1T}{$row}", $this->arredondarNota($mt1));
                }
                if ($mt2 !== null) {
                    $ws->setCellValue("{$c2T}{$row}", $this->arredondarNota($mt2));
                }
                if ($mt3 !== null) {
                    $ws->setCellValue("{$c3T}{$row}", $this->arredondarNota($mt3));
                }
                if ($f !== null) {
                    $ws->setCellValue("{$cF}{$row}", $f);
                }
                if ($mf !== null) {
                    $ws->setCellValue("{$cMF}{$row}", $this->arredondarNota($mf));
                }

                // Estilos base
                foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
                    $ws->getStyle("{$col}{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 9],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                // Cores: notas < 10 → vermelho
                foreach (["{$c1T}{$row}" => $mt1, "{$c2T}{$row}" => $mt2, "{$c3T}{$row}" => $mt3] as $cell => $val) {
                    if ($val !== null) {
                        $ws->getStyle($cell)->applyFromArray([
                            'font' => ['color' => ['rgb' => $val < 10 ? self::COR_VERM_TEXTO : '000000']],
                        ]);
                    }
                }

                // MF: verde se ≥ 10, vermelho se < 10
                if ($mf !== null) {
                    $ws->getStyle("{$cMF}{$row}")->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 9,
                            'bold' => true,
                            'color' => ['rgb' => $mf >= 10 ? self::COR_VERDE_TEXTO : self::COR_VERM_TEXTO],
                        ],
                    ]);
                }

                // Bordas
                $ws->getStyle("{$c1T}{$row}")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
                foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
                    $ws->getStyle("{$col}{$row}")->applyFromArray(['borders' => [
                        'top' => ['borderStyle' => $thin],
                        'bottom' => ['borderStyle' => $thin],
                        'left' => ['borderStyle' => $thin],
                        'right' => ['borderStyle' => $thin],
                    ]]);
                }
                $ws->getStyle("{$cMF}{$row}")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
            }

            // Resultado
            $ws->setCellValue("{$resColLet}{$row}", $res);
            $cRes = match (true) {
                str_contains($res, 'N/TRANSITA') => self::COR_VERM_TEXTO,
                str_contains($res, 'TRANSITA') => self::COR_VERDE_TEXTO,
                default => '000000',
            };
            $ws->getStyle("{$resColLet}{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => $cRes]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['outline' => ['borderStyle' => $thin]],
            ]);

            // Bordas Nº e Nome
            $ws->getStyle("A{$row}")->applyFromArray(['borders' => [
                'left' => ['borderStyle' => $thin],
                'right' => ['borderStyle' => $thin],
                'top' => ['borderStyle' => $thin],
                'bottom' => ['borderStyle' => $thin],
            ]]);
            $ws->getStyle("B{$row}")->applyFromArray(['borders' => [
                'outline' => ['borderStyle' => $thin],
            ]]);
        }

        // 7. LINHAS VAZIAS
        for ($i = count($this->alunos); $i < self::MAX_ALUNOS; $i++) {
            $row = self::DATA_START_ROW + $i;
            $ws->getRowDimension($row)->setRowHeight(14.25);
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->getStyle("A{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $ws->getStyle("A{$row}")->applyFromArray(['borders' => [
                'left' => ['borderStyle' => $thin],
                'top' => ['borderStyle' => $thin],
                'bottom' => ['borderStyle' => $thin],
                'right' => ['borderStyle' => $thin],
            ]]);
            $ws->getStyle("B{$row}")->applyFromArray(['borders' => ['outline' => ['borderStyle' => $thin]]]);
            for ($d = 0; $d < $numDisc; $d++) {
                [$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);
                $ws->getStyle("{$c1T}{$row}")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
                foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
                    $ws->getStyle("{$col}{$row}")->applyFromArray(['borders' => ['outline' => ['borderStyle' => $thin]]]);
                }
                $ws->getStyle("{$cMF}{$row}")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
            }
            $ws->getStyle("{$resColLet}{$row}")->applyFromArray(['borders' => ['outline' => ['borderStyle' => $thin]]]);
        }

        // 8. BORDAS EXTERNAS
        $ultimaLinha = self::DATA_START_ROW + self::MAX_ALUNOS - 1;

        for ($r = 2; $r <= $ultimaLinha; $r++) {
            $ws->getStyle("A{$r}")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
            $ws->getStyle("{$resColLet}{$r}")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
        }
        $ws->getStyle("A2:{$lastColLet}2")->applyFromArray(['borders' => ['top' => ['borderStyle' => $thin]]]);
        $ws->getStyle("A{$ultimaLinha}:{$lastColLet}{$ultimaLinha}")->applyFromArray(['borders' => ['bottom' => ['borderStyle' => $thin]]]);
        $ws->getStyle("A6:{$lastColLet}6")->applyFromArray(['borders' => ['top' => ['borderStyle' => $thin]]]);

        // 9. PÁGINA A4 LANDSCAPE
        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    private function arredondarNota(?float $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        return round($valor, 0, PHP_ROUND_HALF_UP);
    }
}
