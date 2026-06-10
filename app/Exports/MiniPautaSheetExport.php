<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MiniPautaSheetExport implements FromArray, WithTitle, WithEvents
{
    protected string $disciplinaNome;
    protected string $sigla;
    protected array  $alunos;
    protected string $curso;
    protected string $turma;
    protected string $anoLetivo;
    protected string $instituicao;
    protected string $sala;
    protected string $classe;

    const DATA_START_ROW = 11;
    const MAX_ALUNOS     = 40;

    // ── PALETA SEMÂNTICA ─────────────────────────────────────────────────────
    // Azul — institucional: nome da escola, classe, turma, sala, ano letivo
    const COR_AZUL_TEXTO = '1411d9';  // texto azul para labels institucionais

    // Verde — Aprovado / nota positiva (≥ 10)
    const COR_VERDE_TEXTO = '1B5E20';

    // Vermelho — Reprovado / nota negativa (< 10)
    const COR_VERM_TEXTO  = 'B71C1C';

    // Âmbar — Reprovado por Faltas (atenção/risco)
    const COR_AMBAR_TEXTO = '7B4F00';

    // Cinza escuro — cabeçalhos da tabela de notas


    public function __construct(
        string $disciplinaNome,
        string $sigla,
        array $alunos,
        string $curso,
        string $turma,
        string $anoLetivo,
        string $instituicao,
        string $sala,
        string $classe
    ) {
        $this->disciplinaNome = $disciplinaNome;
        $this->sigla          = $sigla;
        $this->alunos         = $alunos;
        $this->curso          = $curso;
        $this->turma          = $turma;
        $this->anoLetivo      = $anoLetivo;
        $this->instituicao    = $instituicao;
        $this->sala           = $sala;
        $this->classe         = $classe;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return mb_strtoupper($this->sigla);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->buildSheet($event->sheet->getDelegate());
            },
        ];
    }

    protected function buildSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        // 1. LARGURAS
        foreach (
            [
                'A' => 4.0,
                'B' => 50.5,
                'D' => 4.6,
                'E' => 4.4,
                'F' => 4.4,
                'G' => 4.7,
                'H' => 4.3,
                'I' => 3.7,
                'J' => 4.4,
                'K' => 4.4,
                'L' => 7.6,
                'M' => 4.1,
                'N' => 3.7,
                'O' => 4.4,
                'P' => 4.4,
                'Q' => 4.7,
                'R' => 5.0,
                'S' => 4.0,
                'T' => 3.6,
                'U' => 12.0,
            ] as $col => $w
        ) {
            $ws->getColumnDimension($col)->setWidth($w);
        }

        // 2. LINHA 2 — Nome da Instituição (fundo azul institucional, texto branco)
        $ws->getRowDimension(2)->setRowHeight(18);
        $ws->mergeCells('A2:U2');
        $ws->setCellValue('A2', mb_strtoupper($this->instituicao));
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 12, 'bold' => false, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 3. LINHAS 3-6 — Estatística + Trimestres + Classe/Turma
        foreach ([3 => 13.5, 4 => 16.5, 5 => 14.25, 6 => 13.5] as $r => $h) {
            $ws->getRowDimension($r)->setRowHeight($h);
        }

        $ws->mergeCells('A3:B3');
        $ws->setCellValue('A3', 'ESTATÍSTICA');
        $ws->getStyle('A3')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach (['D3:H3' => ['D3', '1º TRIM'], 'I3:M3' => ['I3', '2º TRIM'], 'N3:T3' => ['N3', '3º TRIM']] as $m => [$c, $v]) {
            $ws->mergeCells($m);
            $ws->setCellValue($c, $v);
            $ws->getStyle($c)->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        }

        // % Estatística
        $ws->mergeCells('A4:A6');
        $ws->setCellValue('A4', '%');
        $ws->getStyle('A4')->applyFromArray(['font' => ['name' => 'Arial', 'size' => 8], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Baseado na situacao_final que vem da BD via 'resultado'
        $positivas  = count(array_filter($this->alunos, fn($a) => ($a['resultado'] ?? '') === 'APTO'));
        $negativas  = count(array_filter($this->alunos, fn($a) => ($a['resultado'] ?? '') === 'N/APTO'));
        $reprovFalt = count(array_filter($this->alunos, fn($a) => ($a['resultado'] ?? '') === 'N/APTO por Faltas'));

        // Positivas → verde (aprovados)
        $ws->setCellValue('B4', "Positivas: $positivas");
        $ws->getStyle('B4')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Negativas → vermelho (reprovados)
        $ws->setCellValue('B5', "Negativas: $negativas");
        $ws->getStyle('B5')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Rep. Faltas → âmbar (atenção)
        $ws->setCellValue('B6', "Rep. Faltas: $reprovFalt");
        $ws->getStyle('B6')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Assinaturas Sub-Director
        foreach (['D4:H4' => 'D4', 'I4:M4' => 'I4', 'N4:T4' => 'N4'] as $m => $c) {
            $ws->mergeCells($m);
            $ws->setCellValue($c, '      /           /');
            $ws->getStyle($c)->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10]]);
        }
        foreach (['D5:H5' => 'D5', 'I5:M5' => 'I5', 'N5:T5' => 'N5'] as $m => $c) {
            $ws->mergeCells($m);
            $ws->setCellValue($c, 'O Sub-Director');
            $ws->getStyle($c)->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        }
        $ws->mergeCells('D6:H6');
        $ws->mergeCells('I6:M6');
        $ws->mergeCells('N6:T6');

        // Classe / Turma / Ano → azul institucional, fundo azul claro
        $ws->setCellValue('U3', 'CLASSE');
        // Garante que classe é string (evita mostrar JSON do objecto)
        $classeNome = is_array($this->classe) ? ($this->classe['nome'] ?? $this->classe) : $this->classe;
        $ws->setCellValue('U4', $classeNome);
        $ws->setCellValue('U5', 'TURMA');
        $ws->setCellValue('U6', $this->turma);
        foreach (['U3', 'U4', 'U5', 'U6'] as $c) {
            $ws->getStyle($c)->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // 4. LINHAS 7-9 — Curso / Disciplina / Sala
        foreach ([7 => 13.5, 8 => 16.5, 9 => 13.5] as $r => $h) {
            $ws->getRowDimension($r)->setRowHeight($h);
        }

        $ws->mergeCells('A7:C7');
        $ws->setCellValue('A7', 'CURSO: ' . mb_strtoupper($this->curso));
        $ws->getStyle('A7')->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10, 'bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        // Ano letivo → azul institucional
        $ws->setCellValue('U7', $this->anoLetivo);
        $ws->getStyle('U7')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ws->mergeCells('A8:C8');
        $ws->setCellValue('A8', 'DISCIPLINA: ' . mb_strtoupper($this->disciplinaNome));
        $ws->getStyle('A8')->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10, 'bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        foreach (['D8:H9' => ['D8', '1º TRIMESTRE'], 'I8:M9' => ['I8', '2º TRIMESTRE'], 'N8:T9' => ['N8', '3º TRIMESTRE']] as $m => [$c, $v]) {
            $ws->mergeCells($m);
            $ws->setCellValue($c, $v);
            $ws->getStyle($c)->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Sala → azul institucional
        $ws->setCellValue('U8', 'SALA');
        $ws->setCellValue('U9', $this->sala);
        foreach (['U8', 'U9'] as $c) {
            $ws->getStyle($c)->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // 5. CABEÇALHO DA TABELA — Linhas 9-10
        $ws->getRowDimension(10)->setRowHeight(22.5);
        $ws->mergeCells('A9:A10');
        $ws->setCellValue('A9', 'Nº');
        $ws->mergeCells('B9:B10');
        $ws->setCellValue('B9', 'NOME');
        foreach (['A9', 'B9'] as $c) {
            $ws->getStyle($c)->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        //$ws->setCellValue('C9','Nº'); $ws->setCellValue('C10','Proc');

        foreach (
            [
                'D10' => 'MAC',
                'E10' => 'NPP',
                'F10' => 'NPT',
                'G10' => 'MT1',
                'H10' => 'F.I',
                'I10' => 'MAC',
                'J10' => 'NPP',
                'K10' => 'NPT',
                'L10' => 'MT2',
                'M10' => 'F.I',
                'N10' => 'MAC',
                'O10' => 'NPP',
                'P10' => 'NPT',
                'Q10' => 'MT3',
                'R10' => 'MFD',
                'S10' => 'F.I',
                'U10' => 'RESULTADO',
            ] as $cell => $val
        ) {
            $ws->setCellValue($cell, $val);
            $ws->getStyle($cell)->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 9, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        // 6. BORDAS CABEÇALHO
        $thin = $thin = Border::BORDER_THIN;
        $medium = Border::BORDER_MEDIUM;
        $this->applyBorder($ws, 'A9:A10', $medium, $medium, null, $thin);
        $this->applyBorder($ws, 'B9:B10', $thin, $thin, null, $thin);
        foreach (['D10', 'E10', 'F10', 'G10'] as $c) $this->applyBorder($ws, $c, $thin, $thin, $medium, null);
        $this->applyBorder($ws, 'H10', $medium, $thin, $medium, null);
        foreach (['I10', 'J10', 'K10', 'L10'] as $c) $this->applyBorder($ws, $c, $thin, $thin, $medium, null);
        $this->applyBorder($ws, 'M10', $medium, $thin, $medium, null);
        foreach (['N10', 'O10', 'P10', 'Q10', 'R10'] as $c) $this->applyBorder($ws, $c, $thin, $thin, $medium, null);
        $this->applyBorder($ws, 'S10', $medium, $thin, $medium, null);
        $this->applyBorder($ws, 'U10', $thin, $thin, $thin, $thin);

        // 7. DADOS DOS ALUNOS
        foreach ($this->alunos as $i => $aluno) {
            $row  = self::DATA_START_ROW + $i;
            $mfd  = $aluno['mfd'] ?? null;
            $res  = $aluno['resultado'] ?? '';
            $notas = $aluno['notas'] ?? [];

            $ws->getRowDimension($row)->setRowHeight(14.25);

            $ws->setCellValue("A$row", $aluno['numero'] ?? ($i + 1));
            $ws->setCellValue("B$row", $aluno['nome'] ?? '');

            $t1 = $notas[1] ?? [];
            $ws->setCellValue("D$row", $t1['mac'] ?? '');
            $ws->setCellValue("E$row", $t1['npp'] ?? '');
            $ws->setCellValue("F$row", $t1['npt'] ?? '');
            $ws->setCellValue("G$row", $t1['mt'] ?? '');
            $ws->setCellValue("H$row", $t1['faltas'] ?? '');

            $t2 = $notas[2] ?? [];
            $ws->setCellValue("I$row", $t2['mac'] ?? '');
            $ws->setCellValue("J$row", $t2['npp'] ?? '');
            $ws->setCellValue("K$row", $t2['npt'] ?? '');
            $ws->setCellValue("L$row", $t2['mt'] ?? '');
            $ws->setCellValue("M$row", $t2['faltas'] ?? '');

            // Corrigir os valores das colunas T3
            $t3 = $notas[3] ?? [];
            $ws->setCellValue("N$row", $t3['mac']    ?? '');
            $ws->setCellValue("O$row", $t3['npp']    ?? '');
            $ws->setCellValue("P$row", $t3['npt']    ?? '');
            $ws->setCellValue("Q$row", $t3['mt']     ?? '');
            $ws->setCellValue("S$row", $t3['faltas'] ?? '');

            $ws->setCellValue("R$row", $mfd);
            $ws->setCellValue("U$row", $res);

            // Estilo base (preto)
            $ws->getStyle("A$row")->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $ws->getStyle("B$row")->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
            foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'S', 'T'] as $col) {
                $ws->getStyle("{$col}{$row}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // ── Cor do texto das notas por trimestre ──
            // Notas negativas (< 10) → vermelho; positivas → preto (neutro)
            foreach (
                [
                    "D$row" => $t1['mac'] ?? null,
                    "E$row" => $t1['npp'] ?? null,
                    "F$row" => $t1['npt'] ?? null,
                    "G$row" => $t1['mt'] ?? null,
                    "I$row" => $t2['mac'] ?? null,
                    "J$row" => $t2['npp'] ?? null,
                    "K$row" => $t2['npt'] ?? null,
                    "L$row" => $t2['mt'] ?? null,
                    "N$row" => $t3['mac'] ?? null,
                    "O$row" => $t3['npp'] ?? null,
                    "P$row" => $t3['npt'] ?? null,
                    "Q$row" => $t3['mt'] ?? null,
                ] as $celula => $valor
            ) {
                if ($valor !== null && $valor !== '') {
                    $corNota = (float)$valor < 10 ? self::COR_VERM_TEXTO : '000000';
                    $ws->getStyle($celula)->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10, 'color' => ['rgb' => $corNota]],
                    ]);
                }
            }

            // MFD → verde se aprovado, vermelho se reprovado
            // MFD → texto verde se aprovado, vermelho se reprovado
            if ($mfd !== null) {
                $cMfd = $mfd >= 10 ? self::COR_VERDE_TEXTO : self::COR_VERM_TEXTO;
                $ws->getStyle("R$row")->applyFromArray([
                    'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => $cMfd]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // RESULTADO → verde / vermelho / âmbar (semântica clara)
            // RESULTADO → texto verde / vermelho / âmbar (sem fundo)
            // Corrigir o match do resultado (mais específico primeiro)
            $cRes = match (true) {
                str_contains($res, 'N/APTO por Faltas') => self::COR_AMBAR_TEXTO,
                str_contains($res, 'N/APTO')            => self::COR_VERM_TEXTO,
                str_contains($res, 'APTO')              => self::COR_VERDE_TEXTO,
                default                                 => '000000',
            };
            $ws->getStyle("U$row")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => $cRes]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $this->applyDataRowBorders($ws, $row);
        }

        // Linhas vazias
        for ($i = count($this->alunos); $i < self::MAX_ALUNOS; $i++) {
            $row = self::DATA_START_ROW + $i;
            $ws->getRowDimension($row)->setRowHeight(14.25);
            $ws->setCellValue("A$row", $i + 1);
            $ws->getStyle("A$row")->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->applyDataRowBorders($ws, $row);
        }

        // 8. BORDAS EXTERNAS em volta de toda a planilha (A2 até última linha)
        $ultimaLinha = self::DATA_START_ROW + self::MAX_ALUNOS - 1;

        // Borda exterior esquerda — coluna A, do topo ao fundo
        for ($r = 2; $r <= $ultimaLinha; $r++) {
            $ws->getStyle("A$r")->applyFromArray([
                'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }
        // Borda exterior direita — coluna U, do topo ao fundo
        for ($r = 2; $r <= $ultimaLinha; $r++) {
            $ws->getStyle("U$r")->applyFromArray([
                'borders' => ['right' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }
        // Borda exterior topo — linha 2, de A a U
        $ws->getStyle("A2:U2")->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        // Borda exterior fundo — última linha, de A a U
        $ws->getStyle("A{$ultimaLinha}:U{$ultimaLinha}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        // Borda separadora abaixo do cabeçalho institucional (linha 2)
        $ws->getStyle("A2:U2")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        // Borda separadora abaixo dos headers da tabela (linha 10)
        $ws->getStyle("A10:U10")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        // Borda separadora acima do cabeçalho da tabela (linha 9)
        $ws->getStyle("A9:U9")->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // 9. PÁGINA A4 LANDSCAPE
        $ws->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    protected function applyDataRowBorders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, int $row): void
    {
        $this->applyBorder($ws, "A$row", Border::BORDER_MEDIUM, null, Border::BORDER_THIN, Border::BORDER_THIN);
        foreach (['B', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'] as $col) {
            $this->applyBorder($ws, "{$col}{$row}", Border::BORDER_THIN, Border::BORDER_THIN, Border::BORDER_THIN, Border::BORDER_THIN);
        }
    }

    protected function applyBorder(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        string $range,
        ?string $left,
        ?string $right,
        ?string $top,
        ?string $bottom
    ): void {
        $b = [];
        if ($left)   $b['left']  = ['borderStyle' => $left];
        if ($right)  $b['right'] = ['borderStyle' => $right];
        if ($top)    $b['top']   = ['borderStyle' => $top];
        if ($bottom) $b['bottom'] = ['borderStyle' => $bottom];
        if ($b) $ws->getStyle($range)->applyFromArray(['borders' => $b]);
    }
}
