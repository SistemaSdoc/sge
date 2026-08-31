<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PautaFinalSheetExport implements FromArray, WithEvents, WithTitle
{
const DATA_START_ROW = 14;

const HEADER_ROW_SIGLAS = 12;

const HEADER_ROW_SUBCOLS = 13;

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
protected ?string $areaFormacao = null,
protected ?string $director = null,
protected ?string $logoPath = null,
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

// 2. INSÍGNIA + CABEÇALHO INSTITUCIONAL (linhas 1-6)
foreach ([1 => 15, 2 => 15, 3 => 15, 4 => 16, 5 => 16, 6 => 16] as $r => $h) {
$ws->getRowDimension($r)->setRowHeight($h);
}

if ($this->logoPath && file_exists($this->logoPath)) {
$drawing = new Drawing;
$drawing->setName('Insígnia');
$drawing->setDescription('Insígnia');
$drawing->setPath($this->logoPath);
$drawing->setHeight(60); // cobre aprox. linhas 1-6, ajusta se ficar desproporcional
$drawing->setWorksheet($ws);
$centerColIdx = intdiv(2 + $numDisc * 5, 2);
$drawing->setCoordinates($this->colLetter($centerColIdx).'1');
$drawing->setOffsetX(-100);
$drawing->setOffsetY(2);
}

$ws->mergeCells("A4:{$lastColLet}4");
$ws->setCellValue('A4', 'REPÚBLICA DE ANGOLA');
$ws->mergeCells("A5:{$lastColLet}5");
$ws->setCellValue('A5', 'MINISTÉRIO DA EDUCAÇÃO');
$ws->mergeCells("A6:{$lastColLet}6");
$ws->setCellValue('A6', mb_strtoupper($this->instituicao));

foreach (['A4', 'A5', 'A6'] as $c) {
$ws->getStyle($c)->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 12, 'bold' => $c === 'A6', 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
}

// 3. ASSINATURA DO DIRECTOR (linhas 7-8)
$ws->getRowDimension(7)->setRowHeight(14);
$ws->getRowDimension(8)->setRowHeight(14);

$ws->mergeCells('B7:G7');
$ws->setCellValue('B7', 'O Director');
$ws->getStyle('B7')->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 10, 'italic' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$ws->mergeCells('B8:G8');
$ws->setCellValue('B8', $this->director ?? '');
$ws->getStyle('B8')->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// 4. LINHA CLASSE / ANO (linha 10)
$ws->getRowDimension(9)->setRowHeight(6);
$ws->getRowDimension(10)->setRowHeight(15);

$classeNome = is_array($this->classe) ? ($this->classe['nome'] ?? '') : $this->classe;
$ws->setCellValue('B10', "{$classeNome}ª Classe");
$ws->getStyle('B10')->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

$direitaColStart = $this->colLetter(max(2, (2 + $numDisc * 5) - 4));
$ws->mergeCells("{$direitaColStart}10:{$resColLet}10");
$ws->setCellValue("{$direitaColStart}10", "Ano : {$this->anoLetivo}");
$ws->getStyle("{$direitaColStart}10")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// 5. LINHA ÁREA DE FORMAÇÃO / CURSO / SALA (linha 11)
$ws->getRowDimension(11)->setRowHeight(15);

$ws->setCellValue('B11', 'ÁREA DE FORMAÇÃO: '.mb_strtoupper($this->areaFormacao ?? ''));
$ws->getStyle('B11')->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

// "CURSO" centrado aproximadamente no terço do meio da tabela de disciplinas
$cursoIdxIni = max(0, intdiv($numDisc, 3));
$cursoIdxFim = min($numDisc - 1, $cursoIdxIni * 2 + 1);
$cursoColStart = $this->colLetter($this->discBaseIdx($cursoIdxIni));
$cursoColEnd = $this->colLetter($this->discBaseIdx($cursoIdxFim) + 4);
$ws->mergeCells("{$cursoColStart}11:{$cursoColEnd}11");
$ws->setCellValue("{$cursoColStart}11", 'CURSO: '.mb_strtoupper($this->curso));
$ws->getStyle("{$cursoColStart}11")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

$ws->mergeCells("{$direitaColStart}11:{$resColLet}11");
$ws->setCellValue("{$direitaColStart}11", "Sala : {$this->sala}");
$ws->getStyle("{$direitaColStart}11")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// 6. CABEÇALHO DA TABELA — Linhas 12-13
$ws->getRowDimension(self::HEADER_ROW_SIGLAS)->setRowHeight(16.0);
$ws->getRowDimension(self::HEADER_ROW_SUBCOLS)->setRowHeight(14.0);

$r1 = self::HEADER_ROW_SIGLAS;
$r2 = self::HEADER_ROW_SUBCOLS;

// Nº e Nome (merge vertical r1-r2)
$ws->mergeCells("A{$r1}:A{$r2}");
$ws->setCellValue("A{$r1}", 'Nº');
$ws->mergeCells("B{$r1}:B{$r2}");
$ws->setCellValue("B{$r1}", 'NOME');
foreach (["A{$r1}", "B{$r1}"] as $c) {
$ws->getStyle($c)->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
}

// Disciplinas (siglas na linha r1, sub-colunas na linha r2)
for ($d = 0; $d < $numDisc; $d++) {
[$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);
$sigla = mb_strtoupper($this->disciplinas[$d]['sigla'] ?? $this->disciplinas[$d]['nome']);

$ws->mergeCells("{$c1T}{$r1}:{$cMF}{$r1}");
$ws->setCellValue("{$c1T}{$r1}", $sigla);
$ws->getStyle("{$c1T}{$r1}")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
'borders' => ['bottom' => ['borderStyle' => $thin]],
]);

foreach ([
$c1T => 'MT1',
$c2T => 'MT2',
$c3T => 'MT3',
$cF => 'F.I',
$cMF => 'MFD',
] as $col => $label) {
$ws->setCellValue("{$col}{$r2}", $label);
$ws->getStyle("{$col}{$r2}")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
}
}

// Resultado (merge vertical r1-r2)
$ws->mergeCells("{$resColLet}{$r1}:{$resColLet}{$r2}");
$ws->setCellValue("{$resColLet}{$r1}", 'RESULTADO');
$ws->getStyle("{$resColLet}{$r1}")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
]);

// 7. BORDAS CABEÇALHO DA TABELA
$ws->getStyle("A{$r1}:A{$r2}")->applyFromArray(['borders' => [
'left' => ['borderStyle' => $thin],
'right' => ['borderStyle' => $thin],
'top' => ['borderStyle' => $thin],
'bottom' => ['borderStyle' => $thin],
]]);
$ws->getStyle("B{$r1}:B{$r2}")->applyFromArray(['borders' => [
'top' => ['borderStyle' => $thin],
'bottom' => ['borderStyle' => $thin],
'left' => ['borderStyle' => $thin],
'right' => ['borderStyle' => $thin],
]]);

for ($d = 0; $d < $numDisc; $d++) {
[$c1T, $c2T, $c3T, $cF, $cMF] = $this->discCols($d);

$ws->getStyle("{$c1T}{$r1}:{$cMF}{$r1}")->applyFromArray(['borders' => [
'top' => ['borderStyle' => $thin],
'bottom' => ['borderStyle' => $thin],
]]);

foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
$ws->getStyle("{$col}{$r2}")->applyFromArray(['borders' => [
'top' => ['borderStyle' => $thin],
'bottom' => ['borderStyle' => $thin],
'left' => ['borderStyle' => $thin],
'right' => ['borderStyle' => $thin],
]]);
}

$ws->getStyle("{$c1T}{$r2}")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
$ws->getStyle("{$cMF}{$r2}")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
}

$ws->getStyle("{$resColLet}{$r1}:{$resColLet}{$r2}")->applyFromArray(['borders' => [
'outline' => ['borderStyle' => $thin],
]]);

// 8. DADOS DOS ALUNOS
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

foreach ([$c1T, $c2T, $c3T, $cF, $cMF] as $col) {
$ws->getStyle("{$col}{$row}")->applyFromArray([
'font' => ['name' => 'Arial', 'size' => 9],
'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
}

foreach (["{$c1T}{$row}" => $mt1, "{$c2T}{$row}" => $mt2, "{$c3T}{$row}" => $mt3] as $cell => $val) {
if ($val !== null) {
$ws->getStyle($cell)->applyFromArray([
'font' => ['color' => ['rgb' => $val < 10 ? self::COR_VERM_TEXTO : '000000']],
]);
}
}

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

// 9. LINHAS VAZIAS
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

// 10. BORDAS EXTERNAS (apenas em torno da tabela de notas, do cabeçalho r1 até ao fim)
$ultimaLinha = self::DATA_START_ROW + self::MAX_ALUNOS - 1;

for ($r = $r1; $r <= $ultimaLinha; $r++) {
$ws->getStyle("A{$r}")->applyFromArray(['borders' => ['left' => ['borderStyle' => $thin]]]);
$ws->getStyle("{$resColLet}{$r}")->applyFromArray(['borders' => ['right' => ['borderStyle' => $thin]]]);
}
$ws->getStyle("A{$r1}:{$lastColLet}{$r1}")->applyFromArray(['borders' => ['top' => ['borderStyle' => $thin]]]);
$ws->getStyle("A{$ultimaLinha}:{$lastColLet}{$ultimaLinha}")->applyFromArray(['borders' => ['bottom' => ['borderStyle' => $thin]]]);

// 11. PÁGINA A4 LANDSCAPE
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
