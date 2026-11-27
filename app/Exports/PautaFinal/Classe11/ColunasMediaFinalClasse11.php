<?php

namespace App\Exports\PautaFinal\Classe11;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Localiza as médias finais para o quadro de honra da 11.ª classe.
 */
final class ColunasMediaFinalClasse11
{
    /**
     * Descobre a média final ou infere-a da coluna de faltas.
     *
     * @param  list<array{column: int, end_column: int, label: string}>  $blocos
     * @return list<int>
     */
    public function obter(Worksheet $folha, array $blocos): array
    {
        $colunas = [];
        foreach ($blocos as $bloco) {
            $media = null;

            $faltas = null;

            for ($coluna = $bloco['column']; $coluna <= $bloco['end_column']; $coluna++) {
                $rotulo = TextoPlanilha::normalizar(TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 14)));

                if ($rotulo === '') {
                    $rotulo = TextoPlanilha::normalizar(TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 13)));
                }

                if (\in_array($rotulo, ['CFD', 'MFD'], true)) {
                    $media = $coluna;
                }

                if (\in_array($rotulo, ['FINJ', 'FALTAS'], true)) {
                    $faltas = $coluna;
                }
            }

            $media ??= $faltas !== null ? $faltas - 1 : null;

            if ($media === null) {
                throw new InvalidArgumentException('O bloco de uma disciplina não identifica a coluna da média final.');
            }

            $colunas[] = $media;
        }

        return $colunas;
    }
}
