<?php

namespace App\Exports\PautaFinal\Shared\Resultados;

use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\ResultadoPautaVisivel;

/**
 * Constrói os rótulos de resultado e as fórmulas da pauta final.
 */
final class ResultadoPautaFinal
{
    /**
     * Converte o resultado interno para a legenda específica da 12.ª classe.
     *
     * @param  array<string, mixed>  $aluno
     */
    public function classe12(array $aluno): string
    {
        $resultado = ResultadoPautaVisivel::normalizar((string) ($aluno['result'] ?? ''));

        return match (mb_strtolower($resultado, 'UTF-8')) {
            'transita', 'apto', 'pass' => 'APTO',
            'recurso', 'rec', 'reprovado', 'reprovado_negativas' => 'REC.',
            default => mb_strtoupper($resultado, 'UTF-8'),
        };
    }

    /**
     * Constrói a média de honra a partir das colunas finais de cada disciplina.
     *
     * @param  list<int>  $colunasMedias
     */
    public function formulaQuadroHonra(array $colunasMedias, int $linha): string
    {
        $celulas = array_map(
            fn (int $coluna): string => CoordenadasPlanilha::celula($coluna, $linha),
            $colunasMedias,
        );

        return '=SUM('.implode(',', $celulas).')/'.count($colunasMedias);
    }

    /**
     * Constrói o resultado apresentado nos templates da 10.ª e 11.ª classes.
     *
     * @param  array<string, mixed>  $aluno
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     */
    public function classesAnteriores(array $aluno, array $disciplinas): string
    {
        $resultado = ResultadoPautaVisivel::normalizar((string) ($aluno['result'] ?? ''));

        return match ($resultado) {
            'transita' => 'APTO',
            'recurso' => $this->resultadoRecurso($aluno, $disciplinas),
            'reprovado', 'reprovado_negativas' => 'NÃO APTO',
            'EEF' => 'EEF',
            default => mb_strtoupper($resultado, 'UTF-8'),
        };
    }

    /**
     * Lista as disciplinas em recurso com as siglas do template.
     *
     * @param  array<string, mixed>  $aluno
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     */
    private function resultadoRecurso(array $aluno, array $disciplinas): string
    {
        $siglas = collect($aluno['disciplines_recurso'] ?? [])
            ->map(fn (string $id): ?string => collect($disciplinas)->firstWhere('id', $id)['sigla'] ?? null)
            ->filter()
            ->map(fn (string $sigla): string => mb_strtoupper($sigla, 'UTF-8'))
            ->implode(', ');

        return $siglas === '' ? 'REC.' : 'REC. '.$siglas;
    }
}
