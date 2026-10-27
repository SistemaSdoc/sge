<?php

namespace App\Exports\PautaFinal\Shared\Disciplinas;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lê a grelha, os rótulos e as células unidas dos blocos de disciplina.
 */
final class BlocosDisciplinas
{
    private const int LARGURA_BLOCO_REGULAR = 5;

    /**
     * Indica se a folha inclui uma coluna com a média da classe anterior.
     */
    public function temColunasClasseAnterior(Worksheet $folha): bool
    {
        $resultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');

        for ($coluna = 3; $coluna < $resultado; $coluna++) {
            $rotulo = TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 13));
            if (TextoPlanilha::normalizar($rotulo) === '10') {
                return true;
            }
        }

        return false;
    }

    /**
     * Descobre os blocos de disciplinas presentes no template.
     *
     * @return list<array{column: int, end_column: int, label: string}>
     */
    public function obter(Worksheet $folha, bool $layoutClasseAnterior): array
    {
        $resultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');

        if ($layoutClasseAnterior) {
            $inicios = [];
            for ($coluna = 3; $coluna < $resultado; $coluna++) {
                $rotulo = trim(TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 12)));
                if ($rotulo !== '') {
                    $inicios[] = ['column' => $coluna, 'label' => $rotulo];
                }
            }

            $blocos = [];
            foreach ($inicios as $indice => $inicio) {
                $blocos[] = [
                    'column' => $inicio['column'],
                    'end_column' => ($inicios[$indice + 1]['column'] ?? $resultado) - 1,
                    'label' => $inicio['label'],
                ];
            }

            return $blocos;
        }

        if (($resultado - 3) % self::LARGURA_BLOCO_REGULAR !== 0) {
            throw new InvalidArgumentException('A grelha do template final não tem blocos de disciplina regulares.');
        }

        $blocos = [];
        for ($coluna = 3; $coluna < $resultado; $coluna += self::LARGURA_BLOCO_REGULAR) {
            $blocos[] = [
                'column' => $coluna,
                'end_column' => $coluna + self::LARGURA_BLOCO_REGULAR - 1,
                'label' => TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 12)),
            ];
        }

        return $blocos;
    }

    /**
     * Localiza uma disciplina recebida pelo nome ou sigla do título do template.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     */
    public function localizar(string $rotuloTemplate, array $disciplinas): ?int
    {
        $rotulo = TextoPlanilha::normalizar($rotuloTemplate);

        foreach ($disciplinas as $indice => $disciplina) {
            foreach ([$disciplina['sigla'], $disciplina['name']] as $candidato) {
                if ($this->rotulosCorrespondem($rotulo, TextoPlanilha::normalizar($candidato))) {
                    return $indice;
                }
            }
        }

        return null;
    }

    /**
     * Devolve a coluna inicial de cada bloco.
     *
     * @param  list<array{column: int, end_column: int, label: string}>  $blocos
     * @return list<int>
     */
    public function colunas(array $blocos): array
    {
        return array_column($blocos, 'column');
    }

    /**
     * Guarda posições relativas das células unidas dentro de um bloco.
     *
     * @return list<array{int, int, int, int}>
     */
    public function mesclagens(Worksheet $folha, int $colunaInicial, int $colunaFinal): array
    {
        $mesclagens = [];
        foreach (array_values($folha->getMergeCells()) as $intervalo) {
            [$inicio, $fim] = explode(':', $intervalo);
            [$letraInicio, $linhaInicio] = Coordinate::coordinateFromString($inicio);
            [$letraFim, $linhaFim] = Coordinate::coordinateFromString($fim);
            $inicioIndex = Coordinate::columnIndexFromString($letraInicio);
            $fimIndex = Coordinate::columnIndexFromString($letraFim);

            if ($inicioIndex >= $colunaInicial && $fimIndex <= $colunaFinal) {
                $mesclagens[] = [$inicioIndex - $colunaInicial, (int) $linhaInicio, $fimIndex - $colunaInicial, (int) $linhaFim];
            }
        }

        return $mesclagens;
    }

    private function rotulosCorrespondem(string $esquerda, string $direita): bool
    {
        if ($esquerda === $direita) {
            return true;
        }

        $aliases = [
            'BDADOS' => ['BD', 'BASEDADOS', 'BASEDEDADOS'],
            'BD' => ['BDADOS', 'BASEDADOS', 'BASEDEDADOS'],
            'MATEMATICA' => ['MAT'],
            'MAT' => ['MATEMATICA'],
            'REDESDECOMP' => ['REDESDECOMPUTADORES', 'REDESCOMPUTADORES'],
            'NDEDIREITO' => ['NOCOESDEDIREITO'],
        ];

        return in_array($direita, $aliases[$esquerda] ?? [], true)
            || in_array($esquerda, $aliases[$direita] ?? [], true);
    }
}
