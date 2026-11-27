<?php

namespace App\Exports\PautaTrimestral;

use App\Exports\PautaTrimestral\Alunos\LinhasAlunos;
use App\Exports\PautaTrimestral\Alunos\NotasEResultados;
use App\Exports\PautaTrimestral\Assinaturas\AssinaturasTrimestrais;
use App\Exports\PautaTrimestral\Cabecalho\DadosLetivos;
use App\Exports\PautaTrimestral\Cabecalho\IdentificacaoInstitucional;
use App\Exports\PautaTrimestral\Disciplinas\EstruturaDisciplinas;
use App\Exports\PautaTrimestral\Disciplinas\RotulosDisciplinas;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithExportTemplate;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Orquestra a montagem da pauta trimestral a partir do modelo Excel.
 */
readonly class PautaTrimestralExcelExport implements Export, FromArray, WithEvents, WithExportTemplate
{
    /**
     * @param  array<string, string|int|null>  $header
     * @param  list<array{id: string, name: string, sigla: string, tdp_id: string}>  $disciplines
     * @param  list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>  $students
     */
    public function __construct(
        private readonly string $templatePath,
        private readonly array $header,
        private readonly array $disciplines,
        private readonly array $students,
    ) {}

    /**
     * Mantém o export vazio porque o preenchimento acontece no evento AfterSheet.
     */
    public function array(): array
    {
        return [];
    }

    /**
     * Devolve o ficheiro modelo a usar na geração da pauta.
     */
    public function exportTemplate(): string
    {
        return $this->templatePath;
    }

    /**
     * Regista a rotina que preenche a folha depois do modelo ser carregado.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->fillSheet($event->sheet->getDelegate());
            },
        ];
    }

    /**
     * Escreve o cabeçalho, disciplinas, alunos e assinaturas na folha do Excel.
     */
    private function fillSheet(Worksheet $sheet): void
    {
        if ($this->disciplines === []) {
            throw new InvalidArgumentException('A turma precisa de pelo menos uma disciplina para gerar a pauta.');
        }

        $period = (int) ($this->header['period'] ?? 1);

        $periodRoman = $this->periodoRomano($period);

        $assinaturas = new AssinaturasTrimestrais;

        $modeloAssinaturas = $assinaturas->capturarModelo($sheet);

        $dadosLetivos = new DadosLetivos($this->header);

        $modeloCabecalho = $dadosLetivos->capturarModelo($sheet);

        $estruturaDisciplinas = new EstruturaDisciplinas($this->disciplines);

        $estruturaDisciplinas->redimensionarColunas($sheet);

        $colunasDisciplinas = $estruturaDisciplinas->obterColunas($sheet);

        $colunaResultado = $estruturaDisciplinas->obterColunaResultado($sheet);

        $linhasAlunos = new LinhasAlunos($this->students);

        $linhasAlunos->prepararLinhas($sheet);

        $alunosOrdenados = $linhasAlunos->ordenar();

        (new IdentificacaoInstitucional($this->header))->escrever($sheet);

        $dadosLetivos->escrever($sheet, $modeloCabecalho, $colunaResultado, $periodRoman);

        (new RotulosDisciplinas($this->disciplines))->escrever($sheet, $colunasDisciplinas, $period);

        $assinaturas->escrever(
            $sheet,
            $modeloAssinaturas,
            [
                $this->header['class_coordinator'] ?? null,
                $this->header['course_coordinator'] ?? null,
                $this->header['subdirector'] ?? null,
            ],
            20 + max(0, \count($this->students) - 1),
            $colunaResultado + 1,
        );

        $linhasAlunos->escreverIdentificacao($sheet, $alunosOrdenados);

        (new NotasEResultados($this->disciplines))->escrever(
            $sheet,
            $alunosOrdenados,
            $colunasDisciplinas,
            $colunaResultado,
        );

        $linhasAlunos->escreverFecho($sheet, \count($alunosOrdenados));
    }

    private function periodoRomano(int $periodo): string
    {
        $periodos = [1 => 'I', 2 => 'II', 3 => 'III'];

        if (! isset($periodos[$periodo])) {
            throw new InvalidArgumentException('O período da pauta trimestral deve ser 1, 2 ou 3.');
        }

        return $periodos[$periodo];
    }
}
