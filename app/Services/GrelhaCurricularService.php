<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\AnoLectivo;

class GrelhaCurricularService
{
    public function gerarGrelhaCurricular(Aluno $aluno, ?string $anoLectivoId = null)
    {
        $anoLectivoId ??= AnoLectivo::activo()?->id;

        $turma = $aluno->turmas()
            ->where('turmas.ano_lectivo_id', $anoLectivoId)   // ← direto, sem passar por cursoClasseTurno
            ->with('cursoClasseTurno.cursoClasse')
            ->first();
            
        abort_if(!$turma, 404, 'Aluno não tem turma atribuída neste ano lectivo.');

        return $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn($q) => $q
                    ->where('turma_id', $turma->id)
                    ->with('professor.user:id,nome'),
            ])
            ->get()
            ->map(fn($ctd) => [
                'sigla' => $ctd->disciplina->sigla,
                'disciplina' => $ctd->disciplina->nome,
                'professor' => $ctd->turmaDisciplinaProfessores->first()?->professor?->user?->nome
                    ?? 'Sem professor',
            ]);
    }
}
