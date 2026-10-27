<?php

namespace App\Exports\PautaTrimestral\Alunos;

/**
 * Ordena os alunos por nome antes de os escrever na pauta.
 */
readonly class OrdenacaoAlunos
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
        return collect($this->alunos)
            ->sortBy(fn (array $aluno): string => mb_strtolower((string) ($aluno['name'] ?? '')), SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (array $aluno, int $index): array => [
                ...$aluno,
                'number' => $index + 1,
            ])
            ->all();
    }
}
