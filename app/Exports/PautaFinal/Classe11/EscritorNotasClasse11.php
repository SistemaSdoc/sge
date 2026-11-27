<?php

namespace App\Exports\PautaFinal\Classe11;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche notas actuais e médias da 10.ª nos blocos do template da 11.ª.
 */
final class EscritorNotasClasse11
{
    public function __construct(private readonly EstilosPautaFinal $estilos) {}

    /**
     * Escreve as notas de uma disciplina na sua área do template.
     *
     * @param  array{column: int, end_column: int, label: string}  $bloco
     * @param  array{id: string, name: string, sigla: string}  $disciplina
     * @param  array<string, mixed>  $aluno
     */
    public function escrever(Worksheet $folha, array $bloco, array $disciplina, array $aluno, int $linha): void
    {
        $notas = $aluno['grades'][$disciplina['id']] ?? [];

        $mediaAnterior = $aluno['previous_grades'][$disciplina['id']] ?? null;

        for ($coluna = $bloco['column']; $coluna <= $bloco['end_column']; $coluna++) {
            $rotulo = TextoPlanilha::normalizar(TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 14)));
            if ($rotulo === '') {
                $rotulo = TextoPlanilha::normalizar(TextoPlanilha::celula($folha, CoordenadasPlanilha::celula($coluna, 13)));
            }

            $nota = match ($rotulo) {
                '10' => $mediaAnterior,
                'MT1' => $notas['mt1'] ?? null,
                'MT2' => $notas['mt2'] ?? null,
                'MT3' => $notas['mt3'] ?? null,
                'CFD', 'MFD' => $notas['mf'] ?? null,
                'FINJ', 'FALTAS' => $notas['faltas'] ?? null,
                default => null,
            };

            if ($nota === null) {
                continue;
            }

            $celula = CoordenadasPlanilha::celula($coluna, $linha);
            $folha->setCellValue($celula, round((float) $nota, 0, PHP_ROUND_HALF_UP));
            $this->estilos->notaCompacta($folha, $celula);
            if (! in_array($rotulo, ['FINJ', 'FALTAS'], true)) {
                $cor = (float) $nota < 10 ? 'FFFF0000' : 'FF1411D9';
                $folha->getStyle($celula)->getFont()->getColor()->setARGB($cor);
            }
        }
    }

    /**
     * Devolve as colunas de média final para o quadro de honra.
     *
     * @param  list<array{column: int, end_column: int, label: string}>  $blocos
     * @return list<int>
     */
    public function colunasMediaFinal(Worksheet $folha, array $blocos): array
    {
        return (new ColunasMediaFinalClasse11)->obter($folha, $blocos);
    }
}
