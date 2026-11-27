<?php

namespace App\Exports\PautaTrimestral\Disciplinas;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Descobre as colunas das disciplinas e a coluna de resultado no template.
 */
readonly class ColunasDisciplinas
{
    public function __construct(private readonly Worksheet $folha) {}

    /**
     * Devolve a lista das colunas que representam cada disciplina no template.
     *
     * @return list<int>
     */
    public function obterColunas(): array
    {
        $colunas = [];

        foreach (array_keys($this->folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);

            [$letraInicio, $linhaInicio] = Coordinate::coordinateFromString($inicio);

            [$letraFim, $linhaFim] = Coordinate::coordinateFromString($fim);

            $indiceInicio = Coordinate::columnIndexFromString($letraInicio);

            $indiceFim = Coordinate::columnIndexFromString($letraFim);

            $ehColunaDisciplina =
                (int) $linhaInicio === 11
                && (int) $linhaFim === 11
                && $indiceInicio >= 3
                && $indiceFim - $indiceInicio === 2;

            if ($ehColunaDisciplina) {
                $colunas[] = $indiceInicio;
            }
        }

        sort($colunas, SORT_NUMERIC);

        return $colunas;
    }

    /**
     * Localiza a coluna onde aparece o resultado final da pauta.
     */
    public function obterColunaResultado(): int
    {
        $ultimaColuna = Coordinate::columnIndexFromString($this->folha->getHighestColumn());

        for ($coluna = 1; $coluna <= $ultimaColuna; $coluna++) {
            $celula = CoordenadasPlanilha::celula($coluna, 12);

            $valorCelula = mb_strtoupper(trim((string) $this->folha->getCell($celula)->getValue()));

            if ($valorCelula === 'RESULTADO') {
                return $coluna;
            }
        }

        throw new InvalidArgumentException('O modelo não tem a coluna RESULTADO na linha esperada.');
    }
}
