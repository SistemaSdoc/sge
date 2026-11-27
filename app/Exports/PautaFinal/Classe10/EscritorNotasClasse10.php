<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve MT1, MT2, MT3, média final e faltas no bloco da 10.ª classe.
 */
final class EscritorNotasClasse10
{
    public function __construct(private readonly EstilosPautaFinal $estilos) {}

    /**
     * Preenche as notas de uma disciplina na linha do aluno.
     *
     * @param  array{id: string, name: string, sigla: string}  $disciplina
     * @param  array<string, mixed>  $aluno
     */
    public function escrever(Worksheet $folha, int $coluna, array $disciplina, array $aluno, int $linha): void
    {
        $notas = $aluno['grades'][$disciplina['id']] ?? [];

        $valores = [$notas['mt1'] ?? null, $notas['mt2'] ?? null, $notas['mt3'] ?? null, $notas['mf'] ?? null];

        foreach ($valores as $desvio => $nota) {
            if ($nota === null) {
                continue;
            }

            $celula = CoordenadasPlanilha::celula($coluna + $desvio, $linha);

            $folha->setCellValue($celula, $nota);

            $this->estilos->notaCompacta($folha, $celula);

            $folha->getStyle($celula)->getFont()->getColor()->setARGB((float) $nota < 10 ? 'FFFF0000' : 'FF1411D9');
        }

        if (isset($notas['faltas'])) {
            $celula = CoordenadasPlanilha::celula($coluna + 4, $linha);

            $folha->setCellValue($celula, $notas['faltas']);

            $this->estilos->notaCompacta($folha, $celula);
        }
    }
}
