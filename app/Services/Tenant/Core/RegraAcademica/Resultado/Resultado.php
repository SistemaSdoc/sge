<?php

namespace App\Services\Tenant\Core\RegraAcademica\Resultado;

use Illuminate\Support\Collection;

/**
 * Entry point para construir a resposta académica final a partir dos detalhes por disciplina.
 */
class Resultado
{
    public function __construct(
        private readonly ResultadoGlobalResolver $globalResolver,
    ) {}

    /**
     * Resolve a situação global com base nos detalhes por disciplina.
     */
    public function resolver(Collection $detalhes): array
    {
        return $this->globalResolver->resolver($detalhes);
    }

    /**
     * Constrói a resposta final com a ação, a mensagem e os detalhes do aluno.
     */
    public function construir(string $situacao, string $mensagem, array $disciplinas): array
    {
        $acao = match ($situacao) {
            'transita', 'transita_com_deficiencia', 'aprovado_recurso' => 'TRANSITAR',
            'recurso' => 'AGUARDAR_RECURSO',
            'reprovado', 'reprovado_recurso', 'EEF', 'reprovado_negativas' => 'RETER',
            default => 'INCOMPLETO',
        };

        return [
            'resultado' => $situacao,
            'situacao' => $situacao,
            'acao' => $acao,
            'mensagem' => $mensagem,
            'disciplinas' => $disciplinas,
            'detalhes' => $disciplinas,
        ];
    }
}
