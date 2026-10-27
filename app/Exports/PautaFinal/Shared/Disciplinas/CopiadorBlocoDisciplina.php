<?php

namespace App\Exports\PautaFinal\Shared\Disciplinas;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Clona dimensões, estilos, títulos e células unidas de um bloco de disciplina.
 */
final class CopiadorBlocoDisciplina
{
    /**
     * Replica um bloco para a posição de destino.
     *
     * @param  list<array{int, int, int, int}>  $mesclagens
     * @return array{column: int, end_column: int, label: string}
     */
    public function copiar(
        Worksheet $folha,
        int $origemInicial,
        int $origemFinal,
        int $destinoInicial,
        int $ultimaLinha,
        array $mesclagens,
    ): array {
        $largura = $origemFinal - $origemInicial + 1;
        foreach ($mesclagens as [$desvioInicio, $linhaInicio, $desvioFim, $linhaFim]) {
            $folha->mergeCells(sprintf(
                '%s%d:%s%d',
                CoordenadasPlanilha::coluna($destinoInicial + $desvioInicio),
                $linhaInicio,
                CoordenadasPlanilha::coluna($destinoInicial + $desvioFim),
                $linhaFim,
            ));
        }

        for ($desvio = 0; $desvio < $largura; $desvio++) {
            $origem = $origemInicial + $desvio;
            $destino = $destinoInicial + $desvio;
            $letraOrigem = CoordenadasPlanilha::coluna($origem);
            $letraDestino = CoordenadasPlanilha::coluna($destino);
            $dimensaoOrigem = $folha->getColumnDimension($letraOrigem);
            $folha->getColumnDimension($letraDestino)
                ->setWidth($dimensaoOrigem->getWidth())
                ->setVisible($dimensaoOrigem->getVisible());

            for ($linha = 1; $linha <= $ultimaLinha; $linha++) {
                $celulaOrigem = $letraOrigem.$linha;
                $celulaDestino = $letraDestino.$linha;
                $folha->duplicateStyle($folha->getStyle($celulaOrigem), $celulaDestino);

                if (in_array($linha, [13, 14], true)) {
                    $valor = $folha->getCell($celulaOrigem)->getFormattedValue();
                    if ($valor !== '') {
                        $folha->setCellValue($celulaDestino, $valor);
                    }
                }
            }
        }

        return [
            'column' => $destinoInicial,
            'end_column' => $destinoInicial + $largura - 1,
            'label' => '',
        ];
    }
}
