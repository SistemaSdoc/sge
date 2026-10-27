<?php

namespace App\Exports\PautaTrimestral\Cabecalho;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche os dados institucionais do cabeçalho da pauta.
 */
readonly class IdentificacaoInstitucional
{
    /**
     * @param  array<string, string|int|null>  $cabecalho
     */
    public function __construct(private readonly array $cabecalho) {}

    /**
     * Escreve a instituição, o diretor e a classe no respetivo cabeçalho.
     */
    public function escrever(Worksheet $folha): void
    {
        $folha->setCellValue('A6', mb_strtoupper($this->cabecalho['institution'] ?? '', 'UTF-8'));

        $folha->setCellValue('B3', $this->cabecalho['director'] ?? null);

        $folha->setCellValue('B9', $this->cabecalho['class'] ?? '');

        $this->centralizarInsignia($folha);
    }

    /**
     * Centraliza a insígnia no cabeçalho institucional após o ajuste das colunas.
     */
    private function centralizarInsignia(Worksheet $folha): void
    {
        $intervaloInstituicao = collect($folha->getMergeCells())
            ->first(fn (string $intervalo): bool => str_starts_with($intervalo, 'A4:'));

        if ($intervaloInstituicao === null) {
            return;
        }

        [, $celulaFinal] = explode(':', $intervaloInstituicao);

        [$colunaFinal] = Coordinate::coordinateFromString($celulaFinal);

        $indiceColunaFinal = Coordinate::columnIndexFromString($colunaFinal);

        $fonte = $folha->getParent()->getDefaultStyle()->getFont();

        $largurasColunas = [];

        $larguraTotal = 0;

        for ($indiceColuna = 1; $indiceColuna <= $indiceColunaFinal; $indiceColuna++) {
            $letraColuna = Coordinate::stringFromColumnIndex($indiceColuna);

            $largura = SharedDrawing::cellDimensionToPixels(
                $folha->getColumnDimension($letraColuna)->getWidth(),
                $fonte,
            );

            $largurasColunas[$indiceColuna] = $largura;

            $larguraTotal += $largura;
        }

        foreach ($folha->getDrawingCollection() as $desenho) {
            if (! $desenho instanceof Drawing) {
                continue;
            }

            $larguraInsignia = $desenho->getWidth();

            $posicaoEsquerda = ($larguraTotal - $larguraInsignia) / 2;

            $deslocamento = 0;

            foreach ($largurasColunas as $indiceColuna => $larguraColuna) {
                if ($deslocamento + $larguraColuna > $posicaoEsquerda) {
                    [, $linha] = Coordinate::coordinateFromString($desenho->getCoordinates());

                    $desenho->setCoordinates(Coordinate::stringFromColumnIndex($indiceColuna).$linha);

                    $desenho->setOffsetX((int) round($posicaoEsquerda - $deslocamento));

                    return;
                }

                $deslocamento += $larguraColuna;
            }
        }
    }
}
