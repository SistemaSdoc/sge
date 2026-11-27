<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Monta o cabeçalho e os campos letivos do template final da 10.ª classe.
 */
final class EscritorCabecalhoClasse10
{
    /**
     * Escreve os dados institucionais e aplica os estilos preservados do modelo.
     *
     * @param  array<string, string|null>  $cabecalho
     * @param  array<string, array{value: string, style: array}>  $modelo
     */
    public function escrever(Worksheet $folha, array $cabecalho, array $modelo, int $colunaResultado): void
    {
        $folha->setCellValue('A6', mb_strtoupper($cabecalho['institution'] ?? '', 'UTF-8'));

        $folha->setCellValue('B8', $cabecalho['director'] ?? null);

        $classe = trim($cabecalho['class'] ?? '');

        $folha->setCellValue('B10', str_contains(mb_strtolower($classe), 'classe') ? $classe : "{$classe} Classe");

        $inicioCurso = max(3, $colunaResultado - 19);

        $fimArea = $inicioCurso - 1;

        $intervaloArea = CoordenadasPlanilha::celula(2, 11).':'.CoordenadasPlanilha::celula($fimArea, 11);

        if ($fimArea > 2) {
            $folha->mergeCells($intervaloArea);
        }

        $folha->setCellValue('B11', 'AREA DE FORMAÇÃO: '.mb_strtoupper($cabecalho['area'] ?? '', 'UTF-8'));

        $folha->getStyle($intervaloArea)->getAlignment()->setWrapText(false)->setShrinkToFit(true)->setVertical('center');

        $fimCurso = max($inicioCurso, $colunaResultado - 4);

        $intervaloCurso = CoordenadasPlanilha::celula($inicioCurso, 11).':'.CoordenadasPlanilha::celula($fimCurso, 11);

        if ($fimCurso > $inicioCurso) {
            $folha->mergeCells($intervaloCurso);
        }

        $folha->setCellValue(CoordenadasPlanilha::celula($inicioCurso, 11), 'CURSO: '.mb_strtoupper($cabecalho['course'] ?? '', 'UTF-8'));

        $folha->getStyle($intervaloCurso)->applyFromArray($modelo['course']['style']);

        $folha->getStyle($intervaloCurso)->getAlignment()->setHorizontal('center')->setWrapText(true);

        $intervaloAno = CoordenadasPlanilha::celula(max(2, $colunaResultado - 2), 10).':'.CoordenadasPlanilha::celula($colunaResultado + 1, 10);

        $folha->mergeCells($intervaloAno);

        $folha->setCellValue(CoordenadasPlanilha::celula(max(2, $colunaResultado - 2), 10), 'Ano: '.($cabecalho['year'] ?? ''));

        $folha->getStyle($intervaloAno)->applyFromArray($modelo['year']['style']);

        $inicioSala = max(2, $colunaResultado - 1);

        $intervaloSala = CoordenadasPlanilha::celula($inicioSala, 11).':'.CoordenadasPlanilha::celula($colunaResultado + 1, 11);

        $folha->mergeCells($intervaloSala);

        $folha->setCellValue(CoordenadasPlanilha::celula($inicioSala, 11), 'Sala: '.($cabecalho['room'] ?? ''));

        $folha->getStyle($intervaloSala)->applyFromArray($modelo['room']['style']);
    }
}
