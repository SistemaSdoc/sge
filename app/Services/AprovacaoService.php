<?php

namespace App\Services;

use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;

class AprovacaoService
{
    public function __construct(
        private RegraAcademicaService $regraAcademicaService
    ) {
    }

    public function calcularAprovacao(string $turmaAlunoId): array
    {
        $turmaAluno = TurmaAluno::with([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ])->findOrFail($turmaAlunoId);

        return $this->regraAcademicaService
            ->calcularResultadoFinalAluno($turmaAluno);
    }

    // AprovacaoService.php
    public function calcularAprovacaoRecurso(string $turmaAlunoId): array
    {
        $turmaAluno = TurmaAluno::with([
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])->findOrFail($turmaAlunoId);

        return $this->regraAcademicaService->avaliarRecurso($turmaAluno);
    }
}