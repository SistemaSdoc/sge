<?php

namespace App\Exports\PautaTrimestral\Alunos;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Cria o espaço necessário para cada linha de aluno no template.
 */
readonly class EstruturaLinhasAlunos
{
    /**
     * @param  list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     */
    public function __construct(private readonly array $alunos) {}

    /**
     * Duplica a linha base para criar o número de linhas necessário para os alunos.
     */
    public function preparar(Worksheet $folha): void
    {
        $linhasExtra = max(0, \count($this->alunos) - 1);

        if ($linhasExtra === 0) {
            return;
        }

        $linhaModelo = 14;

        $primeiraLinhaNova = 15;

        $folha->insertNewRowBefore($primeiraLinhaNova, $linhasExtra);

        $ultimaColuna = Coordinate::columnIndexFromString($folha->getHighestColumn());

        for ($linha = $primeiraLinhaNova; $linha < $primeiraLinhaNova + $linhasExtra; $linha++) {
            for ($coluna = 1; $coluna <= $ultimaColuna; $coluna++) {
                $letra = CoordenadasPlanilha::letraColuna($coluna);

                $folha->duplicateStyle($folha->getStyle("{$letra}{$linhaModelo}"), "{$letra}{$linha}");
            }

            $folha->getRowDimension($linha)
                ->setRowHeight($folha->getRowDimension($linhaModelo)->getRowHeight());
        }
    }
}
