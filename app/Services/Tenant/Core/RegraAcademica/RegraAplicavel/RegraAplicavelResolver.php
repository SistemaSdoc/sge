<?php

namespace App\Services\Tenant\Core\RegraAcademica\RegraAplicavel;

use App\Models\Tenant\RegraAvaliacao;
use App\Models\Tenant\TurmaAluno;

/**
 * Resolve a regra de avaliação com base na instituição, no ano letivo e na classe.
 */
class RegraAplicavelResolver
{
    /**
     * Resolve a regra aplicável para o aluno e para a classe em análise.
     */
    public function resolve(TurmaAluno $turmaAluno, ?string $classeId = null): ?RegraAvaliacao
    {
        $instituicaoId = $turmaAluno->turma
            ->cursoClasseTurno->cursoClasse->cursoTutelado
            ->instituicao_tutora_id;

        $anoLectivoId = $turmaAluno->turma->ano_lectivo_id;

        $nivelEnsinoId = $turmaAluno->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->nivel_ensino_id;

        return RegraAvaliacao::regraAplicavel(
            instituicaoId: $instituicaoId,
            anoLectivoId: $anoLectivoId,
            classeId: $classeId,
            nivelEnsinoId: $nivelEnsinoId,
        );
    }
}
