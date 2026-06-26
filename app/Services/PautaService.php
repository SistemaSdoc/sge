<?php

namespace App\Services;

use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\Core\RegraAcademicaService;
use Illuminate\Support\Collection;

class PautaService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService
    ) {}

    // ── Ponto de entrada ──────────────────────────────────────────────────

    public function gerarPauta(Turma $turma, string|int $periodo, int $perPage = 20): array
    {
        if ($periodo === 'recurso' || $periodo === 4 || $periodo === '4') {
            return $this->gerarPautaRecurso($turma, $perPage);
        }

        $periodoInt = (is_numeric($periodo) && (int) $periodo > 0)
            ? (int) $periodo
            : null;

        $disciplinas = $this->carregarDisciplinas($turma);

        $paginator = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $periodoInt
                ? $q->where('periodo', $periodoInt)
                : $q->whereIn('periodo', [1, 2, 3, 4]),
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->paginate($perPage);

        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();

        $alunos = $paginator->through(
            function ($ta) use ($disciplinas, $periodoInt, &$offset) {
                $offset++;
                $notasPorTdp = $ta->notas->groupBy('turma_disciplina_professor_id');

                if ($periodoInt) {
                    return [
                        'numero' => $offset,
                        'aluno_id' => $ta->aluno->id,
                        'nome' => $ta->aluno->inscricao?->candidato?->nome,
                        'situacao' => $ta->situacao,
                        'notas' => $this->montarNotasTrimestral($notasPorTdp, $disciplinas, $periodoInt),
                        'resultado' => null,
                    ];
                }

                $resultadoAcademico = $this->regraAcademicaService
                    ->calcularResultadoFinalAluno($ta);

                return [
                    'numero' => $offset,
                    'aluno_id' => $ta->aluno->id,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome,
                    'situacao' => $ta->situacao,
                    'notas' => $this->montarNotasFinal($notasPorTdp, $disciplinas, $resultadoAcademico),
                    'resultado' => $resultadoAcademico['situacao'],
                ];
            }
        );

        return [
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'periodo' => $periodoInt ?? 'final',
            'disciplinas' => $disciplinas->map(fn ($d) => [
                'id' => $d['id'],
                'sigla' => $d['sigla'],
                'nome' => $d['nome'],
            ])->values(),
            'resumo' => $periodoInt ? null : $this->calcularResumo($turma),
            'alunos' => $alunos, // LengthAwarePaginator → Inertia serializa {data, links, meta}
        ];
    }

    // ── Actualizar resultado no turma_aluno (chamar após lançar nota) ─────

    public function actualizarResultadoAluno(TurmaAluno $ta): void
    {
        $resultado = $this->regraAcademicaService
            ->calcularResultadoFinalAluno($ta);

        $ta->update(['resultado' => $resultado['situacao']]);
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    private function carregarDisciplinas(Turma $turma): Collection
    {
        return TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get()
            ->unique('classe_turno_disciplina_id')
            ->map(fn ($tdp) => [
                'id' => $tdp->classeTurnoDisciplina?->disciplina?->id,
                'sigla' => $tdp->classeTurnoDisciplina?->disciplina?->sigla,
                'nome' => $tdp->classeTurnoDisciplina?->disciplina?->nome,
                'tdp_id' => $tdp->id,
            ])
            ->filter(fn ($d) => $d['id'] !== null)
            ->values();
    }

    private function montarNotasFinal(
        Collection $notasPorTdp,
        Collection $disciplinas,
        array $resultadoAcademico
    ): Collection {
        return $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp, $resultadoAcademico) {
            $notas = $notasPorTdp->get($disc['tdp_id'], collect());
            $nota1 = $notas->firstWhere('periodo', 1);
            $nota2 = $notas->firstWhere('periodo', 2);
            $nota3 = $notas->firstWhere('periodo', 3);
            $nota4 = $notas->firstWhere('periodo', 4);
            $detalhe = collect($resultadoAcademico['detalhes'])->firstWhere('disciplina_id', $disc['id']);

            return [
                $disc['id'] => [
                    't1' => $nota1?->media_trimestral,
                    't2' => $nota2?->media_trimestral,
                    't3' => $nota3?->media_trimestral,
                    'mf' => $nota3?->media_final,
                    'nota_recurso' => $nota4?->media_trimestral,
                    'situacao' => $this->resolverSituacaoNota($nota3?->media_final, $detalhe['situacao'] ?? null),
                ],
            ];
        });
    }

    private function montarNotasTrimestral(
        Collection $notasPorTdp,
        Collection $disciplinas,
        int $periodo
    ): Collection {
        return $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp, $periodo) {
            $nota = $notasPorTdp
                ->get($disc['tdp_id'], collect())
                ->firstWhere('periodo', $periodo);

            return [
                $disc['id'] => [
                    'media' => $nota?->media_trimestral,
                    'situacao' => $this->resolverSituacaoNota($nota?->media_trimestral, $nota?->situacao_trimestral),
                ],
            ];
        });
    }

    private function resolverSituacaoNota(?float $media, ?string $situacao): string
    {
        if ($media === null) {
            return 'sem_notas';
        }

        return $situacao ?? 'incompleto';
    }

    private function calcularResumo(Turma $turma): array
    {
        // Query agregada — eficiente, não carrega alunos em memória
        $counts = TurmaAluno::where('turma_id', $turma->id)
            ->where('activo', true)
            ->selectRaw('resultado, COUNT(*) as total')
            ->groupBy('resultado')
            ->pluck('total', 'resultado');

        return [
            'total' => $counts->sum(),
            'transita' => $counts->get('transita', 0),
            'transita_com_deficiencia' => $counts->get('transita_com_deficiencia', 0),
            'recurso' => $counts->get('recurso', 0),
            'reprovados' => $counts->get('reprovado', 0),
            'EEF' => $counts->get('EEF', 0),
            'incompletos' => $counts->get('incompleto', 0),
        ];
    }
}
