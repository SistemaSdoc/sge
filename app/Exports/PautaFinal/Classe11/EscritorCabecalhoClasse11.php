<?php

namespace App\Exports\PautaFinal\Classe11;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Monta o cabeçalho próprio do template final da 11.ª classe.
 */
final class EscritorCabecalhoClasse11
{
    /**
     * Escreve instituição, classe, curso, ano letivo e sala.
     *
     * @param  array<string, string|null>  $cabecalho
     */
    public function escrever(Worksheet $folha, array $cabecalho): void
    {
        $folha->setCellValue('A6', mb_strtoupper($cabecalho['institution'] ?? '', 'UTF-8'));

        $folha->setCellValue('B9', $cabecalho['director'] ?? null);

        $classe = trim($cabecalho['class'] ?? '');

        $folha->setCellValue('B10', str_contains(mb_strtolower($classe), 'classe') ? $classe : "{$classe} Classe");

        $folha->setCellValue('B11', 'AREA DE FORMAÇÃO: '.mb_strtoupper($cabecalho['area'] ?? '', 'UTF-8'));

        $folha->setCellValue('AS11', 'CURSO: '.mb_strtoupper($cabecalho['course'] ?? '', 'UTF-8'));

        $colunaAno = TextoPlanilha::colunaCabecalho($folha, 10, 'Ano');

        $colunaSala = TextoPlanilha::colunaCabecalho($folha, 11, 'Sala');

        $estiloAno = $folha->getStyle(CoordenadasPlanilha::celula($colunaAno, 10))->exportArray();

        $estiloSala = $folha->getStyle(CoordenadasPlanilha::celula($colunaSala, 11))->exportArray();

        foreach (array_values($folha->getMergeCells()) as $intervalo) {
            [$inicio] = explode(':', $intervalo);

            [, $linha] = Coordinate::coordinateFromString($inicio);

            $rotulo = TextoPlanilha::normalizar(TextoPlanilha::celula($folha, $inicio));

            if (((int) $linha === 10 && $rotulo === 'ANO') || ((int) $linha === 11 && $rotulo === 'SALA')) {
                $folha->unmergeCells($intervalo);
            }
        }

        $resultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');

        $inicio = $resultado - 1;

        $intervaloAno = CoordenadasPlanilha::celula($inicio, 10).':'.CoordenadasPlanilha::celula($resultado, 10);

        $intervaloSala = CoordenadasPlanilha::celula($inicio, 11).':'.CoordenadasPlanilha::celula($resultado, 11);

        $folha->mergeCells($intervaloAno);

        $folha->mergeCells($intervaloSala);

        $folha->setCellValue(CoordenadasPlanilha::celula($inicio, 10), 'Ano:');

        $folha->setCellValue(CoordenadasPlanilha::celula($inicio, 11), 'Sala:');

        $folha->setCellValue(CoordenadasPlanilha::celula($resultado + 1, 10), $cabecalho['year'] ?? '');

        $folha->setCellValue(CoordenadasPlanilha::celula($resultado + 1, 11), $cabecalho['room'] ?? '');

        $folha->getStyle($intervaloAno)->applyFromArray($estiloAno);

        $folha->getStyle($intervaloSala)->applyFromArray($estiloSala);
    }
}
