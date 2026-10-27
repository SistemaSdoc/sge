<?php

namespace App\Exports\PautaTrimestral\Disciplinas;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve as siglas e os rótulos dos trimestres nas colunas de disciplina.
 */
readonly class RotulosDisciplinas
{
    /**
     * @param  list<array{id: string, name: string, sigla: string, tdp_id: string}>  $disciplinas
     */
    public function __construct(private readonly array $disciplinas) {}

    /**
     * Escreve a sigla da disciplina e o rótulo MT1, MT2 ou MT3 em cada bloco.
     *
     * @param  list<int>  $colunas
     */
    public function escrever(Worksheet $folha, array $colunas, int $periodo): void
    {
        foreach ($colunas as $indice => $coluna) {
            $letra = CoordenadasPlanilha::letraColuna($coluna);

            $folha->setCellValue("{$letra}11", $this->disciplinas[$indice]['sigla']);

            $folha->setCellValue("{$letra}13", 'MT'.$periodo);
        }
    }
}
