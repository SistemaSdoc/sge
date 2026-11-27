<?php

namespace App\Exports\PautaFinal\Shared\Layout;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Insere a insígnia de Angola quando o modelo não contém uma imagem.
 */
final class InsigniaInstituicao
{
    /**
     * Centra a insígnia na área de cabeçalho unida da instituição.
     */
    public function inserir(Worksheet $folha): void
    {
        $intervalo = collect($folha->getMergeCells())
            ->first(fn (string $mescla): bool => str_starts_with($mescla, 'A4:'));
        if ($intervalo === null) {
            return;
        }

        [, $fim] = explode(':', $intervalo);
        [$colunaFim] = Coordinate::coordinateFromString($fim);
        $ultimaColuna = Coordinate::columnIndexFromString($colunaFim);
        $fonte = $folha->getParent()->getDefaultStyle()->getFont();
        $larguras = [];
        $larguraTotal = 0;

        for ($coluna = 1; $coluna <= $ultimaColuna; $coluna++) {
            $largura = SharedDrawing::cellDimensionToPixels(
                $folha->getColumnDimension(CoordenadasPlanilha::coluna($coluna))->getWidth(),
                $fonte,
            );
            $larguras[$coluna] = $largura;
            $larguraTotal += $largura;
        }

        $larguraImagem = 70;
        $posicaoEsquerda = ($larguraTotal - $larguraImagem) / 2;
        $deslocamento = 0;
        foreach ($larguras as $coluna => $largura) {
            if ($deslocamento + $largura > $posicaoEsquerda) {
                $imagem = new Drawing;
                $imagem->setName('Insígnia de Angola');
                $imagem->setDescription('Insígnia da República de Angola');
                $imagem->setPath(public_path('images/insignia_angola.png'));
                $imagem->setWidth($larguraImagem);
                $imagem->setHeight(56);
                $imagem->setCoordinates(CoordenadasPlanilha::celula($coluna, 1));
                $imagem->setOffsetX((int) round($posicaoEsquerda - $deslocamento));
                $imagem->setOffsetY(8);
                $imagem->setWorksheet($folha);

                return;
            }

            $deslocamento += $largura;
        }
    }
}
