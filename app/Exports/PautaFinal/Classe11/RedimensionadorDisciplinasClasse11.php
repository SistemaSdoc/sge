<?php

namespace App\Exports\PautaFinal\Classe11;

use App\Exports\PautaFinal\Shared\Disciplinas\BlocosDisciplinas;
use App\Exports\PautaFinal\Shared\Disciplinas\CopiadorBlocoDisciplina;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Organiza os blocos com médias históricas usados pela 11.ª classe.
 */
final class RedimensionadorDisciplinasClasse11
{
    public function __construct(
        private readonly BlocosDisciplinas $blocos,
        private readonly CopiadorBlocoDisciplina $copiador,
    ) {}

    /**
     * Ordena disciplinas pelo template e cria blocos para disciplinas adicionais.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return array{disciplinas: list<array{id: string, name: string, sigla: string}>, blocos: array<string, array{column: int, end_column: int, label: string}>}
     */
    public function redimensionar(Worksheet $folha, array $disciplinas): array
    {
        $blocos = $this->blocos->obter($folha, true);
        $restantes = array_values($disciplinas);
        $ordenadas = [];
        $porDisciplina = [];

        foreach ($blocos as $bloco) {
            $indice = $this->blocos->localizar($bloco['label'], $restantes);
            if ($indice === null) {
                continue;
            }

            $disciplina = $restantes[$indice];
            $ordenadas[] = $disciplina;
            $porDisciplina[$disciplina['id']] = $bloco;
            array_splice($restantes, $indice, 1);
        }

        if ($ordenadas === []) {
            throw new InvalidArgumentException('As disciplinas da turma não correspondem aos títulos do template da 11.ª classe.');
        }

        $this->adicionarDisciplinas($folha, $restantes, $blocos, $porDisciplina, $ordenadas);

        return ['disciplinas' => $ordenadas, 'blocos' => $porDisciplina];
    }

    /**
     * Acrescenta um bloco copiado para cada disciplina nova.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $novas
     * @param  list<array{column: int, end_column: int, label: string}>  $blocos
     * @param  array<string, array{column: int, end_column: int, label: string}>  $porDisciplina
     * @param  list<array{id: string, name: string, sigla: string}>  $ordenadas
     */
    private function adicionarDisciplinas(Worksheet $folha, array $novas, array $blocos, array &$porDisciplina, array &$ordenadas): void
    {
        if ($novas === []) {
            return;
        }

        $modelo = $blocos[array_key_last($blocos)];
        $largura = $modelo['end_column'] - $modelo['column'] + 1;
        $mesclagens = $this->blocos->mesclagens($folha, $modelo['column'], $modelo['end_column']);
        $resultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');
        $ultimaLinha = $folha->getHighestRow();
        $folha->insertNewColumnBefore(CoordenadasPlanilha::coluna($resultado), count($novas) * $largura);

        foreach ($novas as $indice => $disciplina) {
            $coluna = $resultado + ($indice * $largura);
            $bloco = $this->copiador->copiar($folha, $modelo['column'], $modelo['end_column'], $coluna, $ultimaLinha, $mesclagens);
            $bloco['label'] = mb_strtoupper($disciplina['sigla'] ?: $disciplina['name'], 'UTF-8');
            $folha->setCellValue(CoordenadasPlanilha::celula($coluna, 12), $bloco['label']);
            $porDisciplina[$disciplina['id']] = $bloco;
            $ordenadas[] = $disciplina;
        }
    }
}
