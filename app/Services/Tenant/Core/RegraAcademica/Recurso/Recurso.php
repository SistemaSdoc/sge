<?php

namespace App\Services\Core\RegraAcademica\Recurso;

use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademica\RegraAplicavel\RegraAplicavel;

/**
 * Entry point para resolver a fase de recurso do aluno.
 */
class Recurso
{
    public function __construct(
        private readonly NotasRecursoLoader $notasRecursoLoader,
        private readonly RecursoStatusResolver $statusResolver,
    ) {}

    /**
     * Resolve o resultado do recurso para o aluno com base nas notas lançadas.
     */
    public function avaliar(
        TurmaAluno $turmaAluno,
        array $resultadoFinal,
        RegraAplicavel $regraAplicavel,
    ): array {
        $turmaAluno = $this->notasRecursoLoader->carregar($turmaAluno);

        return $this->statusResolver->resolver(
            turmaAluno: $turmaAluno,
            resultadoFinal: $resultadoFinal,
            regraAplicavel: $regraAplicavel,
        );
    }
}
