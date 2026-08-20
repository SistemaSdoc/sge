<?php

namespace App\Services\Tenant\Core\RegraAcademica\Contexto;

use App\Models\tenant\TurmaAluno;

/**
 * Carrega as relações necessárias para o contexto académico do aluno.
 */
class ContextoLoader
{
    /**
     * Resolve as relações necessárias para o contexto do aluno.
     */
    public function carregar(TurmaAluno $turmaAluno): TurmaAluno
    {
        $turmaAluno->loadMissing([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'turma.turmaDisciplinaProfessor',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        return $turmaAluno;
    }
}
