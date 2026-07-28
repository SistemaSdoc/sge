<?php

namespace App\Services\Core\RegraAcademica\Contexto;

use App\Models\TurmaAluno;

/**
 * Carrega o contexto académico necessário para avaliar o aluno.
 */
class Contexto
{
    public function __construct(
        private readonly ContextoLoader $contextoLoader,
        private readonly DisciplinasProximaClasseResolver $disciplinasProximaClasseResolver,
    ) {}

    /**
     * Resolve o contexto do aluno com a classe atual, o curso e as disciplinas da próxima classe.
     */
    public function forAluno(TurmaAluno $turmaAluno): array
    {
        $turmaAluno = $this->contextoLoader->carregar($turmaAluno);

        $classeActual = $turmaAluno
            ->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->classe;

        $cursoTutelado = $turmaAluno
            ->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado;

        $disciplinasProximaClasse = $this->disciplinasProximaClasseResolver->resolver(
            $cursoTutelado->id,
            $classeActual->ordem,
        );

        return [
            'classe_actual' => $classeActual,
            'curso_tutelado' => $cursoTutelado,
            'disciplinas_proxima_classe' => $disciplinasProximaClasse,
            'eh_ultima_classe' => is_null($disciplinasProximaClasse),
        ];
    }
}
