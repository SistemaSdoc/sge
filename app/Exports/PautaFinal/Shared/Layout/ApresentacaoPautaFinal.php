<?php

namespace App\Exports\PautaFinal\Shared\Layout;

use App\Exports\PautaFinal\Shared\Disciplinas\BlocosDisciplinas;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ajusta cabeçalhos, legenda, largura do resultado e linhas de alunos.
 */
final class ApresentacaoPautaFinal
{
    private const int LARGURA_BLOCO = 5;

    public function __construct(
        private readonly BlocosDisciplinas $blocos,
        private readonly ResultadoPautaFinal $resultados,
    ) {}

    /**
     * Aplica tamanhos e alinhamentos aos títulos de disciplina.
     *
     * @param  list<int>  $colunasDisciplinas
     */
    public function estilizarDisciplinas(Worksheet $folha, array $colunasDisciplinas, bool $layoutClasseAnterior): void
    {
        if ($layoutClasseAnterior) {
            foreach ($this->blocos->obter($folha, true) as $bloco) {
                $intervalo = CoordenadasPlanilha::celula($bloco['column'], 12).':'.CoordenadasPlanilha::celula($bloco['end_column'], 12);
                $subcabecalho = CoordenadasPlanilha::celula($bloco['column'], 13).':'.CoordenadasPlanilha::celula($bloco['end_column'], 14);

                for ($coluna = $bloco['column']; $coluna <= $bloco['end_column']; $coluna++) {
                    $letra = CoordenadasPlanilha::coluna($coluna);
                    $dimensao = $folha->getColumnDimension($letra);
                    if ($dimensao->getWidth() > 0 && $dimensao->getWidth() < 3.4) {
                        $dimensao->setWidth(3.4);
                    }
                }

                $folha->getStyle($intervalo)->getFont()->setSize(9);
                $folha->getStyle($intervalo)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(false)->setShrinkToFit(true);
                $folha->getStyle($subcabecalho)->getFont()->setSize(8);
                $folha->getStyle($subcabecalho)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(false)->setShrinkToFit(true);
            }

            return;
        }

        foreach ($colunasDisciplinas as $coluna) {
            $intervalo = CoordenadasPlanilha::celula($coluna, 12).':'.CoordenadasPlanilha::celula($coluna + self::LARGURA_BLOCO - 1, 12);
            if (! in_array($intervalo, $folha->getMergeCells(), true)) {
                $folha->mergeCells($intervalo);
            }
            $folha->getStyle($intervalo)->getFont()->setSize(12);
            $folha->getStyle($intervalo)->getAlignment()->setHorizontal('center')->setVertical('center');
        }
    }

    /**
     * Move a legenda de EEF para a coluna B para manter a leitura alinhada.
     */
    public function alinharLegendaEef(Worksheet $folha): void
    {
        if (! in_array('A16:B16', $folha->getMergeCells(), true)) {
            return;
        }

        $texto = TextoPlanilha::celula($folha, 'A16');
        $estilo = $folha->getStyle('A16')->exportArray();
        $folha->unmergeCells('A16:B16');
        $folha->setCellValue('A16', null);
        $folha->setCellValue('B16', $texto);
        $folha->getStyle('B16')->applyFromArray($estilo);
        $folha->getStyle('B16')->getAlignment()->setHorizontal('left');
    }

    /**
     * Aumenta a largura disponível quando o texto mais longo não cabe.
     *
     * @param  list<array<string, mixed>>  $alunos
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     */
    public function ajustarLarguraResultado(Worksheet $folha, int $colunaResultado, array $alunos, array $disciplinas): void
    {
        $larguraTexto = collect($alunos)
            ->map(fn (array $aluno): int => mb_strwidth($this->resultados->classesAnteriores($aluno, $disciplinas), 'UTF-8'))
            ->max() ?? 0;
        $larguraNecessaria = ($larguraTexto * 0.9) + 2;
        $letraResultado = CoordenadasPlanilha::coluna($colunaResultado);
        $letraContinua = CoordenadasPlanilha::coluna($colunaResultado + 1);
        $dimensaoContinua = $folha->getColumnDimension($letraContinua);
        $larguraActual = $folha->getColumnDimension($letraResultado)->getWidth() + $dimensaoContinua->getWidth();

        if ($larguraNecessaria > $larguraActual) {
            $dimensaoContinua->setWidth($dimensaoContinua->getWidth() + ($larguraNecessaria - $larguraActual));
        }
    }
}
