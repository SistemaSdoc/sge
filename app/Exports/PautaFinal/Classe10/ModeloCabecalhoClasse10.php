<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Captura estilos e limpa os campos variáveis do cabeçalho da pauta da 10.ª.
 */
final class ModeloCabecalhoClasse10
{
    /**
     * Guarda os estilos e remove as mesclagens que serão reconstruídas.
     *
     * @return array<string, array{value: string, style: array}>
     */
    public function capturar(Worksheet $folha): array
    {
        $celulas = ['course' => 'AC11', 'year' => 'AT10', 'room' => 'AU11'];

        $modelo = [];

        foreach ($celulas as $campo => $referencia) {
            $modelo[$campo] = [
                'value' => TextoPlanilha::celula($folha, $referencia),
                'style' => $folha->getStyle($referencia)->exportArray(),
            ];
        }

        foreach (array_values($folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);

            [, $linhaInicio] = Coordinate::coordinateFromString($inicio);

            [, $linhaFim] = Coordinate::coordinateFromString($fim);

            if ((int) $linhaInicio === (int) $linhaFim && \in_array((int) $linhaInicio, [10, 11], true)) {
                $folha->unmergeCells($intervalo);
            }
        }

        foreach ($celulas as $referencia) {
            $folha->setCellValue($referencia, null);
        }

        return $modelo;
    }
}
