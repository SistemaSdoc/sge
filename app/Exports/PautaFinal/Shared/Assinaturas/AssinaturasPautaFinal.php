<?php

namespace App\Exports\PautaFinal\Shared\Assinaturas;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preserva o modelo e distribui os responsáveis pelas três áreas de assinatura.
 */
final class AssinaturasPautaFinal
{
    /**
     * Captura rótulos e estilos do modelo standard antes de limpar as células.
     *
     * @return array{labels: list<string>, styles: list<array>}
     */
    public function capturarModelo(Worksheet $folha): array
    {
        return $this->capturar($folha, ['B19', 'Q19', 'AK19'], ['B20', 'P20', 'AK20'], 19, 20);
    }

    /**
     * Escreve assinaturas nas coordenadas fixas do template de classe anterior.
     *
     * @param  list<?string>  $nomes
     */
    public function escreverClasseAnterior(Worksheet $folha, array $nomes, int $linhaTitulo, int $ultimaColuna): void
    {
        $modelo = $this->capturar(
            $folha,
            ["B{$linhaTitulo}", "T{$linhaTitulo}", "AY{$linhaTitulo}"],
            ['B'.($linhaTitulo + 1), 'S'.($linhaTitulo + 1), 'AY'.($linhaTitulo + 1)],
            $linhaTitulo,
            $linhaTitulo + 1,
        );
        $this->escrever($folha, $modelo, $nomes, $linhaTitulo, $ultimaColuna, true);
    }

    /**
     * Distribui rótulos e nomes com larguras proporcionais à área disponível.
     *
     * @param  array{labels: list<string>, styles: list<array>}  $modelo
     * @param  list<?string>  $nomes
     */
    public function escrever(
        Worksheet $folha,
        array $modelo,
        array $nomes,
        int $linhaTitulo,
        int $ultimaColuna,
        bool $ajustarTexto = false,
    ): void {
        $primeiraColuna = 2;
        $totalColunas = $ultimaColuna - $primeiraColuna + 1;
        $larguraBase = intdiv($totalColunas, 3);
        $resto = $totalColunas % 3;
        $colunaActual = $primeiraColuna;

        for ($indice = 0; $indice < 3; $indice++) {
            $largura = $larguraBase + ($indice < $resto ? 1 : 0);
            $fim = $colunaActual + $largura - 1;
            $linhaNome = $linhaTitulo + 1;
            $intervaloTitulo = CoordenadasPlanilha::celula($colunaActual, $linhaTitulo).':'.CoordenadasPlanilha::celula($fim, $linhaTitulo);
            $intervaloNome = CoordenadasPlanilha::celula($colunaActual, $linhaNome).':'.CoordenadasPlanilha::celula($fim, $linhaNome);

            if ($largura > 1) {
                $folha->mergeCells($intervaloTitulo);
                $folha->mergeCells($intervaloNome);
            }

            $folha->setCellValue(CoordenadasPlanilha::celula($colunaActual, $linhaTitulo), $modelo['labels'][$indice]);
            $folha->setCellValue(CoordenadasPlanilha::celula($colunaActual, $linhaNome), $nomes[$indice]);
            $folha->getStyle($intervaloTitulo)->applyFromArray($modelo['styles'][$indice]);
            $folha->getStyle($intervaloNome)->applyFromArray($modelo['styles'][$indice + 3]);
            $folha->getStyle($intervaloTitulo)->getAlignment()->setHorizontal('center')->setWrapText(true)->setShrinkToFit($ajustarTexto);
            $folha->getStyle($intervaloNome)->getAlignment()->setHorizontal('center')->setWrapText(true)->setShrinkToFit($ajustarTexto);
            $colunaActual = $fim + 1;
        }
    }

    /**
     * Captura células modelo e remove mesclagens pré-existentes nessa secção.
     *
     * @param  list<string>  $celulasRotulo
     * @param  list<string>  $celulasNome
     * @return array{labels: list<string>, styles: list<array>}
     */
    private function capturar(Worksheet $folha, array $celulasRotulo, array $celulasNome, int $linhaInicio, int $linhaFim): array
    {
        $rotulos = array_map(fn (string $celula): string => TextoPlanilha::celula($folha, $celula), $celulasRotulo);
        $estilos = array_map(fn (string $celula): array => $folha->getStyle($celula)->exportArray(), [...$celulasRotulo, ...$celulasNome]);

        foreach (array_values($folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);
            [, $linhaInicioMescla] = Coordinate::coordinateFromString($inicio);
            [, $linhaFimMescla] = Coordinate::coordinateFromString($fim);
            if ((int) $linhaInicioMescla >= $linhaInicio && (int) $linhaFimMescla <= $linhaFim) {
                $folha->unmergeCells($intervalo);
            }
        }

        foreach ($celulasNome as $celula) {
            $folha->setCellValue($celula, null);
        }

        return ['labels' => $rotulos, 'styles' => $estilos];
    }
}
