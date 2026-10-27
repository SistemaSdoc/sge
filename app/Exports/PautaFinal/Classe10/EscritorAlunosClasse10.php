<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Monta as linhas de alunos e o quadro de honra da pauta final da 10.ª.
 */
final class EscritorAlunosClasse10
{
    public function __construct(
        private readonly EscritorNotasClasse10 $notas,
        private readonly ResultadoPautaFinal $resultados,
        private readonly EstilosPautaFinal $estilos,
    ) {}

    /**
     * Escreve os alunos em ordem alfabética e preenche notas e resultado.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  list<array<string, mixed>>  $alunos
     * @param  list<int>  $colunasDisciplinas
     * @param  list<int>  $colunasMediaFinal
     */
    public function escrever(
        Worksheet $folha,
        array $disciplinas,
        array $alunos,
        array $colunasDisciplinas,
        int $colunaResultado,
        int $colunaHonra,
        array $colunasMediaFinal,
    ): void {
        $alunos = collect($alunos)->sortBy(fn (array $aluno): string => $aluno['name'] ?? '', SORT_NATURAL | SORT_FLAG_CASE)->values();

        foreach ($alunos as $indice => $aluno) {
            $linha = 15 + $indice;

            $folha->setCellValue("A{$linha}", $indice + 1);

            $folha->getStyle("A{$linha}")->getAlignment()->setHorizontal('center')->setVertical('center');

            $folha->getStyle("A{$linha}")->getFont()->setBold(false)->setSize(11);

            $folha->setCellValue("B{$linha}", $aluno['name'] ?? '');

            foreach ($disciplinas as $indiceDisciplina => $disciplina) {
                $this->notas->escrever(
                    $folha,
                    $colunasDisciplinas[$indiceDisciplina],
                    $disciplina,
                    $aluno,
                    $linha
                );
            }

            $intervalo = CoordenadasPlanilha::celula($colunaResultado, $linha).':'.CoordenadasPlanilha::celula($colunaResultado + 1, $linha);

            $folha->mergeCells($intervalo);

            $folha->setCellValue(
                CoordenadasPlanilha::celula($colunaResultado, $linha),
                $this->resultados->classesAnteriores($aluno, $disciplinas)
            );

            $this->estilos->resultado($folha, $intervalo, (string) ($aluno['result'] ?? ''));

            $folha->setCellValue(
                CoordenadasPlanilha::celula($colunaHonra, $linha),
                $this->resultados->formulaQuadroHonra($colunasMediaFinal, $linha)
            );
        }
    }
}
