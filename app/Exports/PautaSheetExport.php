<?php

/*
namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PautaSheetExport implements FromArray, WithEvents, WithTitle
{
    use Exportable;

    protected array $disciplinas;

    protected array $alunos;

    protected string $curso;

    protected string $turma;

    protected string $anoLetivo;

    protected string $instituicao;

    protected string $sala;

    protected string $classe;

    protected string $periodo;

    protected ?string $areaFormacao;

    protected ?string $director;

    protected ?string $logoPath;

    protected ?string $coordenadorTurma;

    protected ?string $coordenadorCurso;

    protected ?string $subdirectorPedagogico;

    const DATA_START_ROW = 9; // linha onde começam os alunos

    // ── PALETA SEMÂNTICA (só texto, sem fundos) ──────────────────────────────
    const COR_AZUL_TEXTO = '1411d9'; // instituição, classe, turma, sala

    const COR_VERDE_TEXTO = '1B5E20'; // aprovado, nota >= 10

    const COR_VERM_TEXTO = 'B71C1C'; // reprovado, nota < 10

    const COR_AMBAR_TEXTO = '7B4F00'; // reprovado por faltas

    public function __construct(
        array $disciplinas,
        array $alunos,
        string $curso,
        string $turma,
        string $anoLetivo,
        string $instituicao,
        string $sala,
        string $classe,
        string $periodo,
        ?string $areaFormacao = null,
        ?string $director = null,
        ?string $logoPath = null,
        ?string $coordenadorTurma = null,
        ?string $coordenadorCurso = null,
        ?string $subdirectorPedagogico = null
    ) {
        $this->disciplinas = $disciplinas;
        $this->alunos = $alunos;
        $this->curso = $curso;
        $this->turma = $turma;
        $this->anoLetivo = $anoLetivo;
        $this->instituicao = $instituicao;
        $this->sala = $sala;
        $this->classe = $classe;
        $this->periodo = $periodo;
        $this->areaFormacao = $areaFormacao;
        $this->director = $director;
        $this->logoPath = $logoPath;
        $this->coordenadorTurma = $coordenadorTurma;
        $this->coordenadorCurso = $coordenadorCurso;
        $this->subdirectorPedagogico = $subdirectorPedagogico;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'PAUTA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->buildSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function disciplinaNome(array|string $disciplina): string
    {
        if (is_array($disciplina)) {
            return (string) ($disciplina['nome'] ?? $disciplina['sigla'] ?? '');
        }

        return (string) $disciplina;
    }

    private function disciplinaSigla(array|string $disciplina): string
    {
        if (is_array($disciplina)) {
            return (string) ($disciplina['sigla'] ?? mb_substr((string) ($disciplina['nome'] ?? ''), 0, 6));
        }

        return (string) $disciplina;
    }

    protected function buildSheet(Worksheet $ws): void
    {
        $numDisc = count($this->disciplinas);
        $numAlunos = count($this->alunos);

        // Colunas da estrutura:
        // A  = Nº ordem
        // B  = Nome do Aluno
        // C...(C + numDisc*2 - 1) = pares (Nota | Faltas) por disciplina
        // penúltima = Média Anual
        // última    = Resultado

        $colInicio = 3;                          // C = índice 3 (1-based)
        $colFimDisc = $colInicio + ($numDisc * 2) - 1;
        // $colMedia   = $colFimDisc + 1;
        $colResult = $colFimDisc + 1;
        $colMax = $colResult;

        $letraMax = Coordinate::stringFromColumnIndex($colMax);
        $letraMedia = Coordinate::stringFromColumnIndex($colResult);

        // ──────────────────────────────────────────────
        // 1. LARGURAS DAS COLUNAS
        // ──────────────────────────────────────────────
        $ws->getColumnDimension('A')->setWidth(4.5);
        $ws->getColumnDimension('B')->setWidth(38.0);

        for ($d = 0; $d < $numDisc; $d++) {
            $cNota = Coordinate::stringFromColumnIndex($colInicio + $d * 2);
            $cFaltas = Coordinate::stringFromColumnIndex($colInicio + $d * 2 + 1);
            $ws->getColumnDimension($cNota)->setWidth(5.5);
            $ws->getColumnDimension($cFaltas)->setWidth(4.0);
        }

        $ws->getColumnDimension($letraMedia)->setWidth(6.0);
        $ws->getColumnDimension($letraMax)->setWidth(11.0);

        // ──────────────────────────────────────────────
        // 2. LINHA 1 — Nome da Instituição (texto azul)
        // ──────────────────────────────────────────────
        $ws->getRowDimension(1)->setRowHeight(16);
        $ws->mergeCells("A1:{$letraMax}1");
        $ws->setCellValue('A1', mb_strtoupper($this->instituicao));
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 12, 'bold' => false, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ──────────────────────────────────────────────
        // 3. LINHA 2 — Curso
        // ──────────────────────────────────────────────
        $ws->getRowDimension(2)->setRowHeight(14);
        $ws->mergeCells('A2:B2');
        $ws->setCellValue('A2', 'ÁREA DE FORMAÇÃO: '.mb_strtoupper($this->areaFormacao ?? $this->curso));
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Período (direita)
        $periodoLabel = match ($this->periodo) {
            '1' => '1º TRIMESTRE',
            '2' => '2º TRIMESTRE',
            '3' => '3º TRIMESTRE',
            default => 'PAUTA FINAL',
        };
        $colPeriodo = Coordinate::stringFromColumnIndex($colInicio);
        $ws->mergeCells("{$colPeriodo}2:{$letraMax}2");
        $ws->setCellValue("{$colPeriodo}2", $periodoLabel);
        $ws->getStyle("{$colPeriodo}2")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // ──────────────────────────────────────────────
        // 4. LINHA 3 — Curso técnico + Ano letivo / Classe / Turma / Sala
        // ──────────────────────────────────────────────
        $ws->getRowDimension(3)->setRowHeight(14);
        $ws->mergeCells('A3:B3');
        $ws->setCellValue('A3', 'CURSO: '.mb_strtoupper($this->curso));
        $ws->getStyle('A3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Ano | Classe | Turma | Sala (azul, lado direito)
        $classeNome = is_array($this->classe) ? ($this->classe['nome'] ?? '') : $this->classe;
        $infoDir = "Ano: {$this->anoLetivo}    Classe: {$classeNome}    Turma: {$this->turma}    Sala: {$this->sala}";
        $ws->mergeCells("{$colPeriodo}3:{$letraMax}3");
        $ws->setCellValue("{$colPeriodo}3", $infoDir);
        $ws->getStyle("{$colPeriodo}3")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::COR_AZUL_TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // ──────────────────────────────────────────────
        // 5. LINHAS 4-5 — Cabeçalho das disciplinas (nomes verticais)
        // ──────────────────────────────────────────────
        $ws->getRowDimension(4)->setRowHeight(60); // alto para texto rotado
        $ws->getRowDimension(5)->setRowHeight(14);

        // Nº e Nome (span linhas 4-5)
        $ws->mergeCells('A4:A5');
        $ws->setCellValue('A4', 'Nº');
        $ws->getStyle('A4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $ws->mergeCells('B4:B5');
        $ws->setCellValue('B4', 'NOME DO ALUNO');
        $ws->getStyle('B4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Cabeçalho de cada disciplina — nome rotado + sub-headers Nota|Falt
        foreach ($this->disciplinas as $d => $nomeDisciplina) {
            $nomeDisciplinaLabel = $this->disciplinaNome($nomeDisciplina);

            $cNota = Coordinate::stringFromColumnIndex($colInicio + $d * 2);
            $cFaltas = Coordinate::stringFromColumnIndex($colInicio + $d * 2 + 1);

            // Merge das duas colunas para o nome da disciplina (linha 4)
            $ws->mergeCells("{$cNota}4:{$cFaltas}4");
            $ws->setCellValue("{$cNota}4", mb_strtoupper($nomeDisciplinaLabel));
            $ws->getStyle("{$cNota}4")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                    'textRotation' => 90,   // texto vertical (rotado 90°)
                    'wrapText' => true,
                ],
            ]);

            // Sub-headers linha 5: Nota | Falt
            $ws->setCellValue("{$cNota}5", 'Nota');
            $ws->setCellValue("{$cFaltas}5", 'Falt');
            foreach (["{$cNota}5", "{$cFaltas}5"] as $c) {
                $ws->getStyle($c)->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        // Resultado (linhas 4-5 merged)
        $ws->mergeCells("{$letraMax}4:{$letraMax}5");
        $ws->setCellValue("{$letraMax}4", 'RESULTADO');
        $ws->getStyle("{$letraMax}4")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        // ──────────────────────────────────────────────
        // 6. BORDAS DO CABEÇALHO (linhas 4-5)
        // ──────────────────────────────────────────────
        $thin = Border::BORDER_THIN;
        $medium = Border::BORDER_MEDIUM;

        $ws->getStyle("A4:{$letraMax}5")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => $thin],
            ],
        ]);
        // Borda exterior do cabeçalho mais grossa
        $ws->getStyle("A4:{$letraMax}5")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => $medium]],
        ]);

        // ──────────────────────────────────────────────
        // 7. LINHAS DE DADOS DOS ALUNOS
        // ──────────────────────────────────────────────
        foreach ($this->alunos as $i => $aluno) {
            $row = self::DATA_START_ROW + $i - 1; // começa na linha 9
            $ws->getRowDimension($row)->setRowHeight(13.5);

            $notasAluno = $aluno['notas'] ?? [];
            $resultado = $aluno['resultado'] ?? '';

            // Nº e Nome
            $ws->setCellValue("A$row", $aluno['numero'] ?? ($i + 1));
            $ws->setCellValue("B$row", $aluno['nome'] ?? '');
            $ws->getStyle("A$row")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $ws->getStyle("B$row")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            // Notas e faltas por disciplina
            foreach ($this->disciplinas as $d => $nomeDisciplina) {
                $nomeDisciplinaLabel = $this->disciplinaNome($nomeDisciplina);

                $cNota = Coordinate::stringFromColumnIndex($colInicio + $d * 2);
                $cFaltas = Coordinate::stringFromColumnIndex($colInicio + $d * 2 + 1);

                $dadosDisc = $notasAluno[$nomeDisciplinaLabel] ?? null;
                $nota = $dadosDisc['media'] ?? null;
                $faltas = $dadosDisc['faltas'] ?? null;

                $notaArredondada = $this->arredondarNota($nota);
                $ws->setCellValue("{$cNota}{$row}", $notaArredondada !== null ? $notaArredondada : '');
                $ws->setCellValue("{$cFaltas}{$row}", $faltas !== null ? $faltas : '');

                // Cor da nota: vermelho se < 10, verde se >= 10, preto se vazio
                $corNota = '000000';
                if ($notaArredondada !== null) {
                    $corNota = (float) $notaArredondada < 10 ? self::COR_VERM_TEXTO : self::COR_VERDE_TEXTO;
                }
                $ws->getStyle("{$cNota}{$row}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 9, 'color' => ['rgb' => $corNota]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $ws->getStyle("{$cFaltas}{$row}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Resultado
            $ws->setCellValue("{$letraMax}{$row}", mb_strtoupper($resultado));
            $corRes = match (true) {
                str_contains(mb_strtolower($resultado), 'faltas') => self::COR_AMBAR_TEXTO,
                str_contains(mb_strtolower($resultado), 'n/apto') => self::COR_VERM_TEXTO,
                str_contains(mb_strtolower($resultado), 'apto') => self::COR_VERDE_TEXTO,
                default => '000000',
            };
            $ws->getStyle("{$letraMax}{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => ['rgb' => $corRes]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Bordas da linha
            $ws->getStyle("A{$row}:{$letraMax}{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => $thin]],
            ]);
            // Borda esquerda mais grossa no Nº
            $ws->getStyle("A{$row}")->applyFromArray([
                'borders' => ['left' => ['borderStyle' => $medium]],
            ]);
            // Borda direita mais grossa no Resultado
            $ws->getStyle("{$letraMax}{$row}")->applyFromArray([
                'borders' => ['right' => ['borderStyle' => $medium]],
            ]);
        }

        // ──────────────────────────────────────────────
        // 8. BORDAS EXTERNAS da tabela completa
        // ──────────────────────────────────────────────
        $ultimaLinha = self::DATA_START_ROW + $numAlunos - 1;

        // Contorno exterior completo (linha 4 até última linha de alunos)
        $ws->getStyle("A4:{$letraMax}{$ultimaLinha}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => $medium]],
        ]);

        // Borda inferior da última linha de alunos
        $ws->getStyle("A{$ultimaLinha}:{$letraMax}{$ultimaLinha}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => $medium]],
        ]);

        // Separador vertical entre Nome e primeiro par de disciplina
        for ($r = 4; $r <= $ultimaLinha; $r++) {
            $ws->getStyle("B{$r}")->applyFromArray([
                'borders' => ['right' => ['borderStyle' => $medium]],
            ]);
        }

        // Separador vertical antes de Média Anual
        for ($r = 4; $r <= $ultimaLinha; $r++) {
            $ws->getStyle("{$letraMedia}{$r}")->applyFromArray([
                'borders' => ['left' => ['borderStyle' => $medium]],
            ]);
        }

        // ──────────────────────────────────────────────
        // 9. PÁGINA A4 LANDSCAPE (ou portrait se poucas disciplinas)
        // ──────────────────────────────────────────────
        $orientacao = $numDisc > 6
            ? PageSetup::ORIENTATION_LANDSCAPE
            : PageSetup::ORIENTATION_PORTRAIT;

        $ws->getPageSetup()
            ->setOrientation($orientacao)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $ws->getPageMargins()
            ->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    private function arredondarNota(?float $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        return round($valor, 0, PHP_ROUND_HALF_UP);
    }
}*/
