<?php

namespace App\Services\Tenant;

use App\Models\tenant\TurmaAluno;
use App\Services\Core\RegraAcademicaService;

class AprovacaoService
{
    public function __construct(
        private RegraAcademicaService $regraAcademicaService
    ) {}

    public function calcularAprovacao(string $turmaAlunoId): array
    {
        $turmaAluno = TurmaAluno::with([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'turma.turmaDisciplinaProfessor',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ])->findOrFail($turmaAlunoId);

        return $this->regraAcademicaService
            ->resolverSituacaoAcademica($turmaAluno);
    }

    public function calcularAprovacaoRecurso(string $turmaAlunoId): array
    {
        $turmaAluno = TurmaAluno::with([
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.turmaDisciplinaProfessor',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])->findOrFail($turmaAlunoId);

        return $this->regraAcademicaService->resolverSituacaoRecurso($turmaAluno);
    }
}
