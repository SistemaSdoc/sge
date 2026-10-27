<?php

namespace App\Exports\PautaTrimestral\Alunos;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Orquestra a ordenação, o espaço e a identificação dos alunos na pauta.
 */
readonly class LinhasAlunos
{
    /**
     * @param  list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     */
    public function __construct(private readonly array $alunos) {}

    /**
     * Ordena os alunos por nome, mantendo a ordem natural e sem distinguir maiúsculas.
     *
     * @return list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>
     */
    public function ordenar(): array
    {
        return (new OrdenacaoAlunos($this->alunos))->ordenar();
    }

    /**
     * Duplica a linha base para criar o número de linhas necessário para os alunos.
     */
    public function prepararLinhas(Worksheet $folha): void
    {
        (new EstruturaLinhasAlunos($this->alunos))->preparar($folha);
    }

    /**
     * Escreve o número e o nome de cada aluno na sequência correta.
     *
     * @param  list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     */
    public function escreverIdentificacao(Worksheet $folha, array $alunos): void
    {
        (new IdentificacaoAlunos($this->alunos))->escrever($folha, $alunos);
    }

    /**
     * Aplica a linha de fecho da tabela junto ao último aluno registado.
     */
    public function escreverFecho(Worksheet $folha, int $totalAlunos): void
    {
        (new IdentificacaoAlunos($this->alunos))->escreverFecho($folha, $totalAlunos);
    }
}
