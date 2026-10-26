<?php

namespace App\Services\Tenant\QuadroHonra;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Selecciona alunos com notas finais completas e ordena os elegíveis. */
class CalcularCandidatosQuadroHonra
{
    public function __construct(
        private readonly ResolverCriteriosQuadroHonra $resolverCriterios,
    ) {}

    /**
     * @return Collection<int, array{
     *     turma_aluno_id: string,
     *     aluno_id: string,
     *     nome_aluno: string,
     *     matricula: ?string,
     *     classe: string,
     *     curso: string,
     *     turma: string,
     *     media: float,
     *     faltas_total: int,
     *     notas_abaixo_dez: int
     * }>
     */
    public function calcular(
        string $anoLectivoId,
        string $instituicaoId,
        ?string $cursoTuteladoId = null,
        ?float $mediaMinima = null,
        ?int $limiteAlunos = null,
    ): Collection {
        $criterios = $this->resolverCriterios->resolver($mediaMinima, $limiteAlunos);

        $turmasAlunos = TurmaAluno::query()
            ->with([
                'aluno.inscricao.candidato',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'turma.cursoClasseTurno.classeTurnoDisciplinas',
                'turma.turmaDisciplinaProfessor.pautaStatuses',
                'notas' => fn ($query) => $query->whereIn('periodo', [1, 2, 3, 4]),
            ])
            ->where('activo', true)
            ->where('is_historico', false)
            ->whereHas('turma', fn (Builder $query) => $query->where('ano_lectivo_id', $anoLectivoId))
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn (Builder $query) => $query->where('instituicao_id', $instituicaoId),
            )
            ->when(
                $cursoTuteladoId !== null,
                fn (Builder $query) => $query->whereHas(
                    'turma.cursoClasseTurno.cursoClasse',
                    fn (Builder $cursoQuery) => $cursoQuery->where('curso_tutelado_id', $cursoTuteladoId),
                ),
            )
            ->get();

        return $turmasAlunos
            ->map(fn (TurmaAluno $turmaAluno): ?array => $this->calcularAluno(
                $turmaAluno,
                $anoLectivoId,
                $criterios['media_minima'],
            ))
            ->filter(fn (?array $candidato): bool => $candidato !== null)
            ->sort(function (array $a, array $b): int {
                return $this->compararClassificacao($a, $b)
                    ?: strcasecmp($a['nome_aluno'], $b['nome_aluno']);
            })
            ->when(fn (Collection $candidatos): bool => $candidatos->count() > $criterios['limite_alunos'],
                function (Collection $candidatos) use ($criterios): Collection {
                    $limite = $candidatos->take($criterios['limite_alunos']);
                    $ultimoIncluido = $limite->last();

                    return $candidatos
                        ->filter(fn (array $candidato): bool => $this->compararClassificacao($candidato, $ultimoIncluido) <= 0
                        )
                        ->values();
                })
            ->values();
    }

    /**
     * @return array{
     *     turma_aluno_id: string,
     *     aluno_id: string,
     *     nome_aluno: string,
     *     matricula: ?string,
     *     classe: string,
     *     curso: string,
     *     turma: string,
     *     media: float,
     *     faltas_total: int,
     *     notas_abaixo_dez: int
     * }|null
     */
    private function calcularAluno(TurmaAluno $turmaAluno, string $anoLectivoId, float $mediaMinima): ?array
    {
        $turma = $turmaAluno->turma;
        $aluno = $turmaAluno->aluno;

        // Qualquer registo de recurso, inclusive uma nota do quarto período, exclui o aluno.
        if (
            ! $turma
            || ! $aluno
            || in_array($turmaAluno->resultado, ['recurso', 'aprovado_recurso', 'reprovado_recurso'], true)
            || in_array($turmaAluno->situacao, ['recurso', 'aprovado_recurso', 'reprovado_recurso'], true)
            || $turmaAluno->notas->contains(fn (Nota $nota): bool => $nota->periodo === 4)
        ) {
            return null;
        }

        $disciplinasCurriculares = $turma->cursoClasseTurno?->classeTurnoDisciplinas
            ?->where('ano_lectivo_id', $anoLectivoId)
            ->values();

        if (! $disciplinasCurriculares || $disciplinasCurriculares->isEmpty()) {
            return null;
        }

        $turmaDisciplinasProfessor = $turma->turmaDisciplinaProfessor
            ->unique('classe_turno_disciplina_id')
            ->keyBy('classe_turno_disciplina_id');
        $notasPorDisciplina = [];
        $faltasTotal = 0;
        $notasAbaixoDez = 0;

        // A média só é válida quando todas as disciplinas têm os três períodos fechados e nota final.
        foreach ($disciplinasCurriculares as $disciplinaCurricular) {
            $turmaDisciplinaProfessor = $turmaDisciplinasProfessor->get($disciplinaCurricular->id);

            if (! $turmaDisciplinaProfessor) {
                return null;
            }

            $statusPorPeriodo = $turmaDisciplinaProfessor->pautaStatuses->keyBy('periodo');

            foreach ([1, 2, 3] as $periodo) {
                if (! in_array($statusPorPeriodo->get($periodo)?->status, ['finalizada', 'expirada'], true)) {
                    return null;
                }
            }

            $notas = $turmaAluno->notas
                ->where('turma_disciplina_professor_id', $turmaDisciplinaProfessor->id)
                ->keyBy('periodo');
            $notasTrimestrais = collect([1, 2, 3])->map(fn (int $periodo): ?float => $notas->get($periodo)?->media_trimestral
            );

            if ($notasTrimestrais->contains(fn (?float $media): bool => $media === null)) {
                return null;
            }

            $mediaFinal = $notas->get(3)?->media_final;

            if ($mediaFinal === null || $mediaFinal < Nota::NOTA_MINIMA_APTO) {
                return null;
            }

            $notasPorDisciplina[] = (float) $mediaFinal;

            foreach ([1, 2, 3] as $periodo) {
                $nota = $notas->get($periodo);
                $faltasTotal += (int) ($nota?->faltas ?? 0);
                $notasAbaixoDez += (int) ($nota?->media_trimestral < Nota::NOTA_MINIMA_APTO);
            }
        }

        $media = round(array_sum($notasPorDisciplina) / count($notasPorDisciplina), 1, PHP_ROUND_HALF_UP);

        if ($media < $mediaMinima) {
            return null;
        }

        $curso = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome;
        $classe = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome;
        $nomeAluno = $aluno->inscricao?->candidato?->nome;

        if (! $curso || ! $classe || ! $nomeAluno) {
            return null;
        }

        return [
            'turma_aluno_id' => $turmaAluno->id,
            'aluno_id' => $aluno->id,
            'nome_aluno' => $nomeAluno,
            'matricula' => $aluno->matricula,
            'classe' => $classe,
            'curso' => $curso,
            'turma' => $turma->nome,
            'media' => $media,
            'faltas_total' => $faltasTotal,
            'notas_abaixo_dez' => $notasAbaixoDez,
        ];
    }

    /**
     * Compara os critérios de classificação. Resultado negativo significa que $a fica antes de $b.
     *
     * @param  array{media: float, faltas_total: int, notas_abaixo_dez: int}  $a
     * @param  array{media: float, faltas_total: int, notas_abaixo_dez: int}  $b
     */
    private function compararClassificacao(array $a, array $b): int
    {
        // Em caso de empate na média, ficam à frente menos faltas e menos notas abaixo de 10.
        return ($b['media'] <=> $a['media'])
            ?: ($a['faltas_total'] <=> $b['faltas_total'])
            ?: ($a['notas_abaixo_dez'] <=> $b['notas_abaixo_dez']);
    }
}
