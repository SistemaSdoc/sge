<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use Illuminate\Database\Eloquent\Collection;

class NotaAlunoService
{
    public function notas(Aluno $aluno)
    {
        $turmaAluno = $aluno->turmaAlunoActual();

        abort_if(! $turmaAluno, 404, 'Registo do aluno na turma não encontrado.');

        $disciplinasDaTurma = $this->disciplinasDaTurma($turmaAluno->turma);
        $notasPorDisciplina = $this->notasAgrupadasPorDisciplina($turmaAluno);

        return $disciplinasDaTurma->map(
            fn ($tdp) => $this->montarLinhaDisciplina($tdp, $notasPorDisciplina)
        )->values();
    }

    private function disciplinasDaTurma(Turma $turma): Collection
    {
        return $turma->turmaDisciplinaProfessor()
            ->with(['classeTurnoDisciplina.disciplina:id,nome,sigla'])
            ->get()
            ->groupBy(fn ($tdp) => $tdp->classeTurnoDisciplina->disciplina->id)
            ->map(fn ($tdps) => $tdps->first());
    }

    private function notasAgrupadasPorDisciplina(TurmaAluno $turmaAluno): Collection
    {
        return $turmaAluno->notas()
            ->with(['turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome,sigla'])
            ->get()
            ->groupBy(fn ($nota) => $nota->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id);
    }

    private function montarLinhaDisciplina($tdp, Collection $notasPorDisciplina): array
    {
        $disciplina = $tdp->classeTurnoDisciplina->disciplina;
        $notas = $notasPorDisciplina->get($disciplina->id, collect());

        return [
            'id' => $disciplina->id,
            'disciplina' => $disciplina->nome,
            'sigla' => $disciplina->sigla,
            'trimestres' => $this->montarTrimestres($notas),
            'total_faltas' => $notas->sum('faltas'),
            'mediaFinal' => $notas->firstWhere('periodo', 3)?->media_final,
            'status' => $notas->firstWhere('periodo', 3)?->situacao_anual,
        ];
    }

    private function montarTrimestres(\Illuminate\Support\Collection $notasPorDisciplina): array
    {
        return collect([1, 2, 3])->mapWithKeys(function ($periodo) use ($notasPorDisciplina) {
            $nota = $notasPorDisciplina->firstWhere('periodo', $periodo);

            return [$periodo => [
                'provas' => $nota
                    ? [$nota->mac, $nota->nota_prova_professor, $nota->nota_prova_trimestral]
                    : [null, null, null],
                'media' => $nota?->media_trimestral,
                'faltas' => $nota?->faltas,
                'situacao' => $nota?->situacao_trimestral,
            ]];
        })->toArray();
    }
}
