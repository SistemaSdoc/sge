<?php

namespace App\Exports\PautaFinal\Shared\Layout;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Aplica os estilos transversais usados pelas pautas finais.
 */
final class EstilosPautaFinal
{
    private const string COR_APTO = '1B5E20';

    private const string COR_RECURSO = 'FF0000';

    /**
     * Replica o estilo da linha-modelo numa linha de aluno inserida.
     */
    public function clonarLinha(Worksheet $folha, int $linhaModelo, int $linhaDestino): void
    {
        $ultimaColuna = Coordinate::columnIndexFromString($folha->getHighestColumn());

        for ($coluna = 1; $coluna <= $ultimaColuna; $coluna++) {
            $origem = CoordenadasPlanilha::celula($coluna, $linhaModelo);
            $destino = CoordenadasPlanilha::celula($coluna, $linhaDestino);
            $folha->duplicateStyle($folha->getStyle($origem), $destino);
        }
    }

    /**
     * Expande as linhas de alunos usando os estilos e alturas do modelo.
     */
    public function expandirLinhas(Worksheet $folha, int $quantidadeAlunos): void
    {
        $linhasAdicionais = max(0, $quantidadeAlunos - 1);
        if ($linhasAdicionais === 0) {
            return;
        }

        $linhaModelo = 15;
        $primeiraLinha = $linhaModelo + 1;
        $folha->insertNewRowBefore($primeiraLinha, $linhasAdicionais);

        for ($linha = $primeiraLinha; $linha < $primeiraLinha + $linhasAdicionais; $linha++) {
            $this->clonarLinha($folha, $linhaModelo, $linha);
            $folha->getRowDimension($linha)->setRowHeight($folha->getRowDimension($linhaModelo)->getRowHeight());
        }
    }

    /**
     * Aplica alinhamento compacto aos valores numéricos da pauta.
     */
    public function notaCompacta(Worksheet $folha, string $celula): void
    {
        $folha->getStyle($celula)->getFont()->setSize(9);
        $folha->getStyle($celula)->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center')
            ->setWrapText(false)
            ->setShrinkToFit(true);
    }

    /**
     * Aplica tipografia e cor ao resultado de um aluno.
     */
    public function resultado(Worksheet $folha, string $intervalo, string $resultado): void
    {
        $cor = match (true) {
            $resultado === 'transita' => self::COR_APTO,
            in_array($resultado, ['recurso', 'reprovado', 'reprovado_negativas', 'EEF'], true) => self::COR_RECURSO,
            default => '000000',
        };

        $folha->getStyle($intervalo)->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => ['rgb' => $cor]],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => false, 'shrinkToFit' => true],
        ]);
    }
}
