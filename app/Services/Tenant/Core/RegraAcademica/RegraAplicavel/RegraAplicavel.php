<?php

namespace App\Services\Tenant\Core\RegraAcademica\RegraAplicavel;

use App\Models\tenant\RegraAvaliacao;
use App\Models\tenant\TurmaAluno;

/**
 * Entry point para resolver a regra de avaliação aplicável ao aluno.
 */
class RegraAplicavel
{
    public function __construct(
        private readonly RegraAplicavelResolver $resolver,
    ) {}

    /**
     * Resolve a regra de avaliação correta para o aluno no contexto recebido.
     */
    public function resolve(TurmaAluno $turmaAluno, ?string $classeId = null): ?RegraAvaliacao
    {
        return $this->resolver->resolve($turmaAluno, $classeId);
    }
}
