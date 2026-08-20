<?php

namespace App\Services\Tenant;

use App\Helpers\ArredondamentoHelper;
use App\Models\tenant\Aluno;
use App\Models\tenant\PautaStatus;
use App\Models\tenant\Turma;
use App\Models\tenant\TurmaAluno;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Database\Eloquent\Collection;

class NotaAlunoService
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function notas(Aluno $aluno, ?string $classeId = null)
    {
        $turmaAluno = $this->obterTurmaAlunoDaClasse($aluno, $classeId);

        if (! $turmaAluno) {
            return collect();
        }

        $disciplinasDaTurma = $this->disciplinasDaTurma($turmaAluno->turma);
        $notasPorDisciplina = $this->notasAgrupadasPorDisciplina($turmaAluno);

        return $disciplinasDaTurma->map(
            fn ($tdp) => $this->montarLinhaDisciplina($tdp, $notasPorDisciplina)
        )->values();
    }

    public function classesDisponiveis(Aluno $aluno): array
    {
        return TurmaAluno::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('turma.cursoClasseTurno.cursoClasse.classe')
            ->with('turma.cursoClasseTurno.cursoClasse.classe')
            ->get()
            ->map(fn (TurmaAluno $turmaAluno) => $turmaAluno->turma?->cursoClasseTurno?->cursoClasse?->classe)
            ->filter()
            ->unique('id')
            ->sortBy('nome')
            ->map(fn ($classe) => [
                'id' => $classe->id,
                'nome' => $classe->nome,
            ])
            ->values()
            ->toArray();
    }

    private function obterTurmaAlunoDaClasse(Aluno $aluno, ?string $classeId = null): ?TurmaAluno
    {
        $query = TurmaAluno::query()
            ->where('aluno_id', $aluno->id)
            ->with(['turma.anoLectivo', 'turma.cursoClasseTurno.cursoClasse.classe']);

        if ($classeId) {
            $query->whereHas('turma.cursoClasseTurno.cursoClasse.classe', function ($q) use ($classeId) {
                $q->where('classes.id', $classeId);
            });
        }

        return $query
            ->orderByDesc('activo')
            ->orderByDesc('created_at')
            ->first();
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
        $notas = $turmaAluno->notas()
            ->with(['turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome,sigla'])
            ->get();

        // Carregar todos os PautaStatus relevantes de uma vez
        $tdpIds = $notas->pluck('turma_disciplina_professor_id')->unique();
        $statusMap = PautaStatus::whereIn('turma_disciplina_professor_id', $tdpIds)
            ->get()
            ->groupBy('turma_disciplina_professor_id')
            ->map(fn ($group) => $group->keyBy('periodo'));

        return $notas
            ->filter(function ($nota) use ($statusMap) {
                $status = $statusMap
                    ->get($nota->turma_disciplina_professor_id)
                    ?->get($nota->periodo);

                // Sem status ou rascunho → esconder do aluno
                return $status && $status->status !== 'rascunho';
            })
            ->groupBy(fn ($nota) => $nota->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id);
    }

    private function montarLinhaDisciplina($tdp, Collection $notasPorDisciplina): array
    {
        $disciplina = $tdp->classeTurnoDisciplina->disciplina;
        $notas = $notasPorDisciplina->get($disciplina->id, collect());

        $notaPeriodo3 = $notas->firstWhere('periodo', 3);

        return [
            'id' => $disciplina->id,
            'disciplina' => $disciplina->nome,
            'sigla' => $disciplina->sigla,
            'trimestres' => $this->montarTrimestres($notas),
            'total_faltas' => $notas->sum('faltas'),
            'mediaFinal' => ArredondamentoHelper::roundToHalf($notaPeriodo3?->media_final),
            'status' => $notaPeriodo3?->situacao_anual,
        ];
    }

    private function montarTrimestres(\Illuminate\Support\Collection $notasPorDisciplina): array
    {
        return collect([1, 2, 3])->mapWithKeys(function ($periodo) use ($notasPorDisciplina) {
            $nota = $notasPorDisciplina->firstWhere('periodo', $periodo);

            return [
                $periodo => [
                    'provas' => $nota ? [
                        ArredondamentoHelper::roundToHalf($nota->mac),
                        ArredondamentoHelper::roundToHalf($nota->nota_prova_professor),
                        ArredondamentoHelper::roundToHalf($nota->nota_prova_trimestral),
                    ] : [null, null, null],
                    'media' => ArredondamentoHelper::roundToHalf($nota?->media_trimestral),
                    'faltas' => $nota?->faltas,
                    'situacao' => $nota?->situacao_trimestral,
                ],
            ];
        })->toArray();
    }
}
