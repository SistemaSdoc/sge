<?php

namespace App\Exports\PautaFinal\Shared\Suporte;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Converte índices de linha e coluna em referências de células Excel.
 */
final class CoordenadasPlanilha
{
    /**
     * Devolve a letra correspondente a um índice de coluna Excel.
     */
    public static function coluna(int $indice): string
    {
        return Coordinate::stringFromColumnIndex($indice);
    }

    /**
     * Devolve uma referência Excel, por exemplo A1 ou BC12.
     */
    public static function celula(int $coluna, int $linha): string
    {
        return self::coluna($coluna).$linha;
    }
}
