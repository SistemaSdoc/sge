<?php

namespace App\Services;

use App\Models\Aluno;

class GrelhaCurricularService
{
    public function gerarGrelhaCurricular(Aluno $aluno)
    {
        $turmaAtual = $aluno->turmaActual()
            ->with('cursoClasseTurno.cursoClasse')
            ->first();

        abort_if(! $turmaAtual, 404, 'Aluno não tem turma atribuída no ano lectivo atual.');

        return $turmaAtual->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($q) => $q
                    ->where('turma_id', $turmaAtual->id)
                    ->with('professor.user:id,nome'),
            ])
            ->get()
            ->map(fn ($ctd) => [
                'sigla' => $ctd->disciplina->sigla,
                'disciplina' => $ctd->disciplina->nome,
                'professor' => $ctd->turmaDisciplinaProfessores->first()?->professor?->user?->nome
                    ?? 'Sem professor',
            ]);
    }
}
