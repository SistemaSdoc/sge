<?php

namespace App\Exports\PautaTrimestral\Disciplinas;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ajusta a estrutura das colunas de disciplina ao número real de cadeiras.
 */
readonly class EstruturaDisciplinas
{
    /**
     * @param  list<array{id: string, name: string, sigla: string, tdp_id: string}>  $disciplinas
     */
    public function __construct(private readonly array $disciplinas) {}

    /**
     * Expande ou reduz as colunas da disciplina para corresponder ao modelo atual.
     */
    public function redimensionarColunas(Worksheet $folha): void
    {
        $colunas = $this->obterColunas($folha);

        (new ExpansaoDisciplinas($folha, $this->disciplinas, $colunas))->redimensionar();
    }

    /**
     * Devolve a lista das colunas que representam cada disciplina no template.
     *
     * @return list<int>
     */
    public function obterColunas(Worksheet $folha): array
    {
        return (new ColunasDisciplinas($folha))->obterColunas();
    }

    /**
     * Localiza a coluna onde aparece o resultado final da pauta.
     */
    public function obterColunaResultado(Worksheet $folha): int
    {
        return (new ColunasDisciplinas($folha))->obterColunaResultado();
    }
}
