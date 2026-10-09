<?php

namespace App\Services\Tenant;

use App\Helpers\ArredondamentoHelper;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Database\Eloquent\Collection;

class NotaAlunoService
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {}

    public function notas(Aluno $aluno, ?string $classeId = null)
    {
        return $this->carregarDadosAluno($aluno, $classeId, false)['notas'];
    }

    /**
     * @return array{notas: Collection, resultado_final: ?array<string, mixed>}
     */
    public function dadosAluno(Aluno $aluno, ?string $classeId = null): array
    {
        return $this->carregarDadosAluno($aluno, $classeId, true);
    }

    private function carregarDadosAluno(Aluno $aluno, ?string $classeId, bool $incluirResultadoFinal): array
    {
        $turmaAluno = $this->obterTurmaAlunoDaClasse($aluno, $classeId);

        if (! $turmaAluno) {
            return [
                'notas' => collect(),
                'resultado_final' => null,
            ];
        }

        $disciplinasDaTurma = $this->disciplinasDaTurma($turmaAluno->turma);
        $notasVisiveis = $this->notasVisiveis($turmaAluno);
        $turmaAluno->setRelation('notas', $notasVisiveis);
        $notasPorDisciplina = $notasVisiveis
            ->groupBy(fn ($nota) => $nota->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id);

        return [
            'notas' => $disciplinasDaTurma->map(
                fn ($tdp) => $this->montarLinhaDisciplina($tdp, $notasPorDisciplina)
            )->values(),
            'resultado_final' => $incluirResultadoFinal
                ? $this->resolverResultadoFinal($turmaAluno, $disciplinasDaTurma, $notasPorDisciplina)
                : null,
        ];
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

    private function notasVisiveis(TurmaAluno $turmaAluno): Collection
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

                return $this->notaVisivelParaAluno($nota, $status);
            })
            ->values();
    }

    private function notaVisivelParaAluno(Nota $nota, ?PautaStatus $status): bool
    {
        return $nota->periodo === 4 || ($status && $status->status !== 'rascunho');
    }

    private function resolverResultadoFinal(
        TurmaAluno $turmaAluno,
        Collection $disciplinas,
        \Illuminate\Support\Collection $notasPorDisciplina,
    ): array {
        $resultadoAcademico = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAluno);
        $resultadoRecurso = $this->regraAcademicaService->resolverSituacaoRecurso($turmaAluno);

        $detalhesRecurso = collect($resultadoRecurso['detalhes'] ?? [])
            ->keyBy('disciplina_id');
        $detalhesAcademicos = collect($resultadoAcademico['detalhes'] ?? [])
            ->keyBy('disciplina_id');
        $detalhesDisciplinas = $disciplinas
            ->map(function ($tdp) use ($detalhesAcademicos, $detalhesRecurso, $notasPorDisciplina, $resultadoAcademico): array {
                $disciplina = $tdp->classeTurnoDisciplina->disciplina;
                $notas = $notasPorDisciplina->get($disciplina->id, collect());
                $mediasTrimestrais = collect([1, 2, 3])
                    ->map(fn (int $periodo) => $notas->firstWhere('periodo', $periodo)?->media_trimestral);
                $notaRecurso = $notas->firstWhere('periodo', 4);
                $detalhe = $detalhesAcademicos->get($disciplina->id, []);
                $detalheRecurso = $notaRecurso
                    ? $detalhesRecurso->get($disciplina->id)
                    : null;
                $mfd = $mediasTrimestrais->contains(fn ($media): bool => $media === null)
                    ? null
                    : round((float) $mediasTrimestrais->avg(), 0, PHP_ROUND_HALF_UP);

                return [
                    'disciplina_id' => $disciplina->id,
                    'disciplina' => $detalhe['disciplina'] ?? $disciplina->nome,
                    'sigla' => $disciplina->sigla,
                    'situacao' => $detalhe['situacao'] ?? ($resultadoAcademico['situacao'] === 'EEF' ? 'EEF' : 'incompleto'),
                    'media_final' => $detalhe['media_final'] ?? null,
                    'mt1' => ArredondamentoHelper::roundToHalf($mediasTrimestrais[0]),
                    'mt2' => ArredondamentoHelper::roundToHalf($mediasTrimestrais[1]),
                    'mt3' => ArredondamentoHelper::roundToHalf($mediasTrimestrais[2]),
                    'total_faltas' => $notas->whereIn('periodo', [1, 2, 3])->sum('faltas'),
                    'mfd' => $mfd,
                    'nota_recurso' => ArredondamentoHelper::roundToHalf($notaRecurso?->media_trimestral),
                    'situacao_recurso' => $detalheRecurso['situacao'] ?? null,
                    'media_recurso' => $detalheRecurso['media_recurso'] ?? null,
                ];
            })
            ->values();

        $situacaoAcademica = $resultadoAcademico['situacao'];

        return [
            'situacao' => $situacaoAcademica,
            'situacao_academica' => $situacaoAcademica,
            'situacao_recurso' => $resultadoRecurso['situacao'] ?? null,
            'disciplinas' => $detalhesDisciplinas->values(),
        ];
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
