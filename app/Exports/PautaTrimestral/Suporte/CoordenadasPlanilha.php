<?php

namespace App\Exports\PautaTrimestral\Suporte;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Helper para converter índices de linha e coluna em referências Excel.
 */
class CoordenadasPlanilha
{
    /**
     * Converte uma coluna e uma linha em uma referência como A1 ou BC12.
     */
    public static function celula(int $coluna, int $linha): string
    {
        return Coordinate::stringFromColumnIndex($coluna).$linha;
    }

    /**
     * Devolve a letra correspondente a uma coluna Excel.
     */
    public static function letraColuna(int $coluna): string
    {
        return Coordinate::stringFromColumnIndex($coluna);
    }
}
