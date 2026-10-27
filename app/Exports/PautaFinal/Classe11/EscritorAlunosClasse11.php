<?php

namespace App\Exports\PautaFinal\Classe11;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Monta as linhas de alunos com notas históricas na pauta final da 11.ª.
 */
final class EscritorAlunosClasse11
{
    public function __construct(
        private readonly EscritorNotasClasse11 $notas,
        private readonly ResultadoPautaFinal $resultados,
        private readonly EstilosPautaFinal $estilos,
    ) {}

    /**
     * Preenche alunos, notas, resultado e média de honra.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  list<array<string, mixed>>  $alunos
     * @param  array<string, array{column: int, end_column: int, label: string}>  $blocosPorDisciplina
     * @param  list<int>  $colunasMediaFinal
     */
    public function escrever(
        Worksheet $folha,
        array $disciplinas,
        array $alunos,
        array $blocosPorDisciplina,
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

            foreach ($disciplinas as $disciplina) {
                $this->notas->escrever(
                    $folha,
                    $blocosPorDisciplina[$disciplina['id']],
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
