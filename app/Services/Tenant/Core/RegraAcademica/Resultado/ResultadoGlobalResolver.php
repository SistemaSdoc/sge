<?php

namespace App\Services\Core\RegraAcademica\Resultado;

use Illuminate\Support\Collection;

/**
 * Resolve a situação global do aluno a partir dos detalhes por disciplina.
 */
class ResultadoGlobalResolver
{
    /**
     * Resolve a situação final global, por exemplo transita, recurso ou reprovado.
     */
    public function resolver(Collection $detalhes): array
    {
        $situacaoGlobal = match (true) {
            $detalhes->contains('situacao', 'reprovado') => 'reprovado',
            $detalhes->contains('situacao', 'recurso') => 'recurso',
            $detalhes->contains('situacao', 'transita_com_deficiencia') => 'transita_com_deficiencia',
            default => 'transita',
        };

        $mensagem = match ($situacaoGlobal) {
            'transita' => 'Aluno aprovado em todas as disciplinas.',
            'transita_com_deficiencia' => 'Aluno transita com deficiência.',
            'recurso' => 'Aluno vai ao recurso.',
            'reprovado' => 'Aluno reprovado.',
            'EEF' => 'Aluno reprovado por faltas.',
            default => 'Situação indefinida.',
        };

        return [
            'situacao' => $situacaoGlobal,
            'mensagem' => $mensagem,
        ];
    }
}
