<?php

namespace App\Exports\MiniPauta\Alunos;

use App\Exports\ResultadoPautaVisivel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve identificação, notas e resultados de cada aluno.
 */
final class EscritorLinhasAlunosMiniPauta
{
    /**
     * @param  array<int, array<string, mixed>>  $alunos
     */
    public function escrever(Worksheet $folha, array $alunos, int $linhaInicio): void
    {
        foreach ($alunos as $indice => $aluno) {
            $linha = $linhaInicio + $indice;
            $folha->getStyle("A{$linha}:U{$linha}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $folha->getStyle("B{$linha}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $notas = $aluno['notas'] ?? [];
            $folha->setCellValue("A{$linha}", $aluno['numero'] ?? $indice + 1);
            $folha->setCellValue("B{$linha}", $aluno['nome'] ?? '');

            $this->escreverTrimestre($folha, $linha, $notas[1] ?? [], 'C', 'D', 'E', 'F', 'G');
            $this->escreverTrimestre($folha, $linha, $notas[2] ?? [], 'H', 'I', 'J', 'K', 'L');
            $this->escreverTrimestre($folha, $linha, $notas[3] ?? [], 'M', 'N', 'O', 'P', 'R');

            $folha->setCellValue("Q{$linha}", $aluno['mfd'] ?? null);
            $folha->setCellValue("S{$linha}", $notas[3]['faltas'] ?? '');
            $folha->setCellValue("T{$linha}", $notas[3]['faltas'] ?? '');
            $folha->setCellValue("U{$linha}", ResultadoPautaVisivel::normalizar((string) ($aluno['resultado'] ?? '')));
        }
    }

    /**
     * @param  array<string, mixed>  $notas
     */
    private function escreverTrimestre(Worksheet $folha, int $linha, array $notas, string $colunaMac, string $colunaNpp, string $colunaNpt, string $colunaMedia, string $colunaFaltas): void
    {
        $folha->setCellValue("{$colunaMac}{$linha}", $notas['mac'] ?? '');
        $folha->setCellValue("{$colunaNpp}{$linha}", $notas['npp'] ?? '');
        $folha->setCellValue("{$colunaNpt}{$linha}", $notas['npt'] ?? '');
        $folha->setCellValue("{$colunaMedia}{$linha}", $notas['mt'] ?? '');
        $folha->setCellValue("{$colunaFaltas}{$linha}", $notas['faltas'] ?? '');
    }
}
