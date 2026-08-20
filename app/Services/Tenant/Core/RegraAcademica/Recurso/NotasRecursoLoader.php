<?php

namespace App\Services\Core\RegraAcademica\Recurso;

use App\Models\TurmaAluno;

/**
 * Carrega as relações necessárias para a fase de recurso.
 */
class NotasRecursoLoader
{
    /**
     * Resolve as relações necessárias para avaliar o recurso do aluno.
     */
    public function carregar(TurmaAluno $turmaAluno): TurmaAluno
    {
        $turmaAluno->loadMissing([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        return $turmaAluno;
    }
}
