<?php

namespace App\Exports\PautaFinal\Classe13;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche o layout e as notas históricas do template final da 13.ª classe.
 */
final class GeradorPautaFinalClasse13
{
    /**
     * @param  array<string, string|null>  $cabecalho
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  list<array<string, mixed>>  $alunos
     */
    public function __construct(
        private readonly array $cabecalho,
        private readonly array $disciplinas,
        private readonly array $alunos,
        private readonly PautaFinalDisciplineMapper $disciplineMapper,
    ) {}

    /**
     * Insere as linhas de aluno e preenche histórico, notas e resultados.
     */
    public function gerar(Worksheet $folha): void
    {
        if ($this->disciplinas === []) {
            throw new InvalidArgumentException('A turma precisa de pelo menos uma disciplina para gerar a pauta final.');
        }

        $alunos = collect($this->alunos)
            ->sortBy(fn (array $aluno): string => $aluno['name'] ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $linhaModelo = 13;
        $linhasAdicionais = max(0, $alunos->count() - 1);
        $alturaLinha = $folha->getRowDimension($linhaModelo)->getRowHeight();

        // A linha 14 contém blocos mesclados de resumo; as linhas novas entram antes dela.
        if ($linhasAdicionais > 0) {
            $folha->insertNewRowBefore($linhaModelo + 1, $linhasAdicionais);
        }

        (new EscritorCabecalhoClasse13)->escrever($folha, $this->cabecalho, $linhasAdicionais);

        $estilos = new EstilosPautaFinal;
        $notas = new EscritorNotasClasse13($this->disciplineMapper);
        $resultados = new ResultadoPautaFinal;
        $colunasQuadroHonra = $notas->colunasQuadroHonra($this->disciplinas);

        foreach ($alunos as $indice => $aluno) {
            $linha = $linhaModelo + $indice;

            if ($indice > 0) {
                $estilos->clonarLinha($folha, $linhaModelo, $linha);
                $folha->getRowDimension($linha)->setRowHeight($alturaLinha);
            }

            $folha->setCellValue('A'.$linha, $indice + 1);
            $folha->setCellValue('B'.$linha, $aluno['number'] ?? ($indice + 1));
            $folha->setCellValue('C'.$linha, $aluno['name'] ?? '');
            $notas->escrever($folha, $linha, $aluno, $this->disciplinas);

            $resultado = $resultados->classesAnteriores($aluno, $this->disciplinas);
            $folha->setCellValue('BB'.$linha, $resultado);
            $estilos->resultado($folha, 'BB'.$linha, (string) ($aluno['result'] ?? ''));

            if ($colunasQuadroHonra !== []) {
                $celulas = array_map(fn (string $coluna): string => $coluna.$linha, $colunasQuadroHonra);
                $folha->setCellValue('BC'.$linha, '=SUM('.implode(',', $celulas).')/'.count($celulas));
            }
        }

        $folha->getPageSetup()->setPrintArea('A1:BC'.$folha->getHighestRow());
    }
}
