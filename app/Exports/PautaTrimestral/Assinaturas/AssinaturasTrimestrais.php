<?php

namespace App\Exports\PautaTrimestral\Assinaturas;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preserva o layout das assinaturas e preenche os nomes nas três secções.
 */
readonly class AssinaturasTrimestrais
{
    /**
     * Guarda os rótulos e os estilos das assinaturas antes de limpar o modelo.
     *
     * @return array{rotulos: list<string>, estilos: list<array>}
     */
    public function capturarModelo(Worksheet $folha): array
    {
        $celulasRotulo = ['B20', 'D20', 'U20'];

        $celulasNome = ['B21', 'D21', 'U21'];

        $rotulos = array_map(fn (string $celula): string => $this->textoCelula($folha, $celula), $celulasRotulo);

        $estilos = array_map(fn (string $celula): array => $folha->getStyle($celula)->exportArray(), [...$celulasRotulo, ...$celulasNome]);

        foreach (array_values($folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);

            [, $linhaInicio] = Coordinate::coordinateFromString($inicio);

            [, $linhaFim] = Coordinate::coordinateFromString($fim);

            if ((int) $linhaInicio >= 20 && (int) $linhaFim <= 21) {
                $folha->unmergeCells($intervalo);
            }
        }

        foreach ($celulasNome as $celula) {
            $folha->setCellValue($celula, null);
        }

        return ['rotulos' => $rotulos, 'estilos' => $estilos];
    }

    /**
     * Distribui os nomes pelas três áreas de assinatura, mantendo o mesmo desenho do template.
     *
     * @param  array{rotulos: list<string>, estilos: list<array>}  $modelo
     * @param  list<?string>  $nomes
     */
    public function escrever(
        Worksheet $folha,
        array $modelo,
        array $nomes,
        int $linhaTitulo,
        int $ultimaColuna
    ): void {
        $primeiraColuna = 2;

        $totalColunas = $ultimaColuna - $primeiraColuna + 1;

        $larguraBase = intdiv($totalColunas, 3);

        $resto = $totalColunas % 3;

        $colunaActual = $primeiraColuna;

        for ($indice = 0; $indice < 3; $indice++) {
            $largura = $larguraBase + ($indice < $resto ? 1 : 0);

            $ultimaColunaSecao = $colunaActual + $largura - 1;

            $intervaloTitulo = CoordenadasPlanilha::celula($colunaActual, $linhaTitulo).':'.CoordenadasPlanilha::celula($ultimaColunaSecao, $linhaTitulo);

            $linhaNome = $linhaTitulo + 1;

            $intervaloNome = CoordenadasPlanilha::celula($colunaActual, $linhaNome).':'.CoordenadasPlanilha::celula($ultimaColunaSecao, $linhaNome);

            if ($largura > 1) {
                $folha->mergeCells($intervaloTitulo);
                $folha->mergeCells($intervaloNome);
            }

            $folha->setCellValue(CoordenadasPlanilha::celula($colunaActual, $linhaTitulo), $modelo['rotulos'][$indice]);

            $folha->setCellValue(CoordenadasPlanilha::celula($colunaActual, $linhaNome), $nomes[$indice]);

            $folha->getStyle($intervaloTitulo)->applyFromArray($modelo['estilos'][$indice]);

            $folha->getStyle($intervaloNome)->applyFromArray($modelo['estilos'][$indice + 3]);

            $folha->getStyle($intervaloTitulo)->getAlignment()->setHorizontal('center')->setWrapText(true);

            $folha->getStyle($intervaloNome)->getAlignment()->setHorizontal('center')->setWrapText(true);

            $colunaActual = $ultimaColunaSecao + 1;
        }
    }

    /**
     * Extrai o texto simples de uma célula, incluindo conteúdos em RichText.
     */
    private function textoCelula(Worksheet $folha, string $celula): string
    {
        $valor = $folha->getCell($celula)->getValue();

        return $valor instanceof RichText ? $valor->getPlainText() : (string) ($valor ?? '');
    }
}
