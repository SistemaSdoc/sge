<?php

namespace App\Exports\PautaTrimestral\Disciplinas;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Expande o bloco de disciplinas para o número de cadeiras do ano letivo.
 */
readonly class ExpansaoDisciplinas
{
    /**
     * @param  list<array{id: string, name: string, sigla: string, tdp_id: string}>  $disciplinas
     * @param  list<int>  $colunasAtuais
     */
    public function __construct(
        private readonly Worksheet $folha,
        private readonly array $disciplinas,
        private readonly array $colunasAtuais,
    ) {}

    /**
     * Ajusta as colunas do template ao número de disciplinas em uso.
     */
    public function redimensionar(): void
    {
        $totalActual = \count($this->colunasAtuais);

        $colunaResultado = (new ColunasDisciplinas($this->folha))->obterColunaResultado();

        if (\count($this->disciplinas) < $totalActual) {
            $indiceUltimaColuna = \count($this->disciplinas);

            $this->folha->removeColumn(
                CoordenadasPlanilha::letraColuna($this->colunasAtuais[$indiceUltimaColuna]),
                ($totalActual - \count($this->disciplinas)) * 3,
            );

            return;
        }

        if (\count($this->disciplinas) === $totalActual) {
            return;
        }

        $colunaOrigem = $this->colunasAtuais[$totalActual - 1];

        $fimOrigem = $colunaOrigem + 2;

        $ultimaLinha = $this->folha->getHighestRow();

        $celulasUnidas = $this->obterCelulasUnidas($colunaOrigem, $fimOrigem);

        $blocosExtra = \count($this->disciplinas) - $totalActual;

        $this->folha->insertNewColumnBefore(
            CoordenadasPlanilha::letraColuna($colunaResultado),
            $blocosExtra * 3,
        );

        for ($indiceBloco = 0; $indiceBloco < $blocosExtra; $indiceBloco++) {
            $inicioDestino = $colunaResultado + ($indiceBloco * 3);

            foreach ($celulasUnidas as [$desvioInicio, $linhaInicio, $desvioFim, $linhaFim]) {
                $this->folha->mergeCells(\sprintf(
                    '%s%d:%s%d',
                    CoordenadasPlanilha::letraColuna($inicioDestino + $desvioInicio),
                    $linhaInicio,
                    CoordenadasPlanilha::letraColuna($inicioDestino + $desvioFim),
                    $linhaFim,
                ));
            }

            for ($desvioColuna = 0; $desvioColuna < 3; $desvioColuna++) {
                $origem = $colunaOrigem + $desvioColuna;

                $destino = $inicioDestino + $desvioColuna;

                $letraOrigem = CoordenadasPlanilha::letraColuna($origem);

                $letraDestino = CoordenadasPlanilha::letraColuna($destino);

                $dimensaoOrigem = $this->folha->getColumnDimension($letraOrigem);

                $this->folha->getColumnDimension($letraDestino)
                    ->setWidth($dimensaoOrigem->getWidth())
                    ->setVisible($dimensaoOrigem->getVisible());

                for ($linha = 1; $linha <= $ultimaLinha; $linha++) {
                    $celulaOrigem = "{$letraOrigem}{$linha}";

                    $celulaDestino = "{$letraDestino}{$linha}";

                    $this->folha->duplicateStyle($this->folha->getStyle($celulaOrigem), $celulaDestino);

                    if (\in_array($linha, [12, 13], true)) {
                        $this->folha->setCellValue($celulaDestino, $this->folha->getCell($celulaOrigem)->getFormattedValue());
                    }
                }
            }
        }
    }

    /**
     * Reúne as células fundidas dentro do intervalo de colunas da disciplina.
     *
     * @return list<array{int, int, int, int}>
     */
    private function obterCelulasUnidas(int $colunaInicial, int $colunaFinal): array
    {
        $celulasUnidas = [];

        foreach (array_values($this->folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);

            [$letraInicio, $linhaInicio] = Coordinate::coordinateFromString($inicio);

            [$letraFim, $linhaFim] = Coordinate::coordinateFromString($fim);

            $indiceInicio = Coordinate::columnIndexFromString($letraInicio);

            $indiceFim = Coordinate::columnIndexFromString($letraFim);

            if ($indiceInicio >= $colunaInicial && $indiceFim <= $colunaFinal) {
                $celulasUnidas[] = [
                    $indiceInicio - $colunaInicial,
                    (int) $linhaInicio,
                    $indiceFim - $colunaInicial,
                    (int) $linhaFim,
                ];
            }
        }

        return $celulasUnidas;
    }
}
