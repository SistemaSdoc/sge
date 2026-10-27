<?php

namespace App\Exports\PautaTrimestral\Alunos;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve os números, nomes e o fecho da tabela dos alunos.
 */
readonly class IdentificacaoAlunos
{
    /**
     * @param  list<array{number?: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     */
    public function __construct(private readonly array $alunos) {}

    /**
     * Escreve o número e o nome de cada aluno na sequência correta.
     *
     * @param  list<array{number?: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     */
    public function escrever(Worksheet $folha, array $alunos): void
    {
        foreach ($alunos as $indice => $aluno) {
            $linha = 14 + $indice;

            $celulaNumero = "A{$linha}";
            $numeroAluno = $aluno['number'] ?? ($indice + 1);

            $folha->setCellValue($celulaNumero, $numeroAluno);

            $this->aplicarEstiloNumeroAluno($folha, $celulaNumero);

            $folha->setCellValue("B{$linha}", $aluno['name']);
        }
    }

    /**
     * Aplica a linha de fecho da tabela junto ao último aluno registado.
     */
    public function escreverFecho(Worksheet $folha, int $totalAlunos): void
    {
        $ultimaLinha = 13 + $totalAlunos;

        $folha->getStyle("A{$ultimaLinha}")
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM);
    }

    /**
     * Centraliza e limpa o estilo do número do aluno na célula correspondente.
     */
    private function aplicarEstiloNumeroAluno(Worksheet $folha, string $celula): void
    {
        $folha->getStyle($celula)->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center')
            ->setShrinkToFit(true);

        $folha->getStyle($celula)->getFont()
            ->setBold(false)
            ->setSize(11);
    }
}
