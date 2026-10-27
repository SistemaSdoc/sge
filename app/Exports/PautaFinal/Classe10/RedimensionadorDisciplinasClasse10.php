<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Disciplinas\BlocosDisciplinas;
use App\Exports\PautaFinal\Shared\Disciplinas\CopiadorBlocoDisciplina;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Organiza e redimensiona os blocos regulares usados pela 10.ª classe.
 */
final class RedimensionadorDisciplinasClasse10
{
    private const int LARGURA_BLOCO = 5;

    public function __construct(
        private readonly BlocosDisciplinas $blocos,
        private readonly CopiadorBlocoDisciplina $copiador,
    ) {}

    /**
     * Ordena as disciplinas pelo template e ajusta a quantidade de blocos.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return list<array{id: string, name: string, sigla: string}>
     */
    public function redimensionar(Worksheet $folha, array $disciplinas): array
    {
        $blocos = $this->blocos->obter($folha, false);

        $restantes = array_values($disciplinas);

        $ordenadas = [];

        $colunasLivres = [];

        foreach ($blocos as $bloco) {
            $indice = $this->blocos->localizar($bloco['label'], $restantes);
            if ($indice === null) {
                $colunasLivres[] = $bloco['column'];

                continue;
            }

            $ordenadas[] = $restantes[$indice];

            array_splice($restantes, $indice, 1);
        }

        if ($ordenadas === []) {
            throw new InvalidArgumentException('As siglas do template final não correspondem às disciplinas da turma.');
        }

        rsort($colunasLivres);

        foreach ($colunasLivres as $coluna) {
            $folha->removeColumn(CoordenadasPlanilha::coluna($coluna), self::LARGURA_BLOCO);
        }

        if ($restantes === []) {
            return $ordenadas;
        }

        $this->adicionarDisciplinas($folha, $restantes);

        return [...$ordenadas, ...$restantes];
    }

    /**
     * Copia o último bloco antes do resultado para cada disciplina adicional.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $novas
     */
    private function adicionarDisciplinas(Worksheet $folha, array $novas): void
    {
        $colunas = $this->blocos->colunas($this->blocos->obter($folha, false));

        $origem = $colunas[array_key_last($colunas)];

        $fimOrigem = $origem + self::LARGURA_BLOCO - 1;

        $mesclagens = $this->blocos->mesclagens($folha, $origem, $fimOrigem);

        $resultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');

        $ultimaLinha = $folha->getHighestRow();

        $folha->insertNewColumnBefore(CoordenadasPlanilha::coluna($resultado), \count($novas) * self::LARGURA_BLOCO);

        foreach ($novas as $indice => $disciplina) {
            $destino = $resultado + ($indice * self::LARGURA_BLOCO);

            $this->copiador->copiar($folha, $origem, $fimOrigem, $destino, $ultimaLinha, $mesclagens);

            $folha->setCellValue(CoordenadasPlanilha::celula($destino, 12), mb_strtoupper($disciplina['sigla'], 'UTF-8'));
        }
    }
}
