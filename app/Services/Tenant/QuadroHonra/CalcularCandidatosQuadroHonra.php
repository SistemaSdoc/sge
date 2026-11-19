<?php

namespace App\Services\Tenant\QuadroHonra;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Selecciona alunos com notas trimestrais fechadas e ordena os elegíveis. */
class CalcularCandidatosQuadroHonra
{
    public function __construct(
        private readonly ResolverCriteriosQuadroHonra $resolverCriterios,
        private readonly AvaliarMediaQuadroHonra $avaliarMedia,
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
        ?float $mediaMinima = null,
        ?int $limiteAlunos = null,
        int $periodo = 3,
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
            ->get();

        return $turmasAlunos
            ->map(fn (TurmaAluno $turmaAluno): ?array => $this->calcularAluno(
                $turmaAluno,
                $anoLectivoId,
                $criterios['media_minima'],
                $periodo,
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
    private function calcularAluno(
        TurmaAluno $turmaAluno,
        string $anoLectivoId,
        float $mediaMinima,
        int $periodo,
    ): ?array {
        $turma = $turmaAluno->turma;
        $aluno = $turmaAluno->aluno;
        $classe = $turma?->cursoClasseTurno?->cursoClasse?->classe;

        if (
            ! $turma
            || ! $aluno
            || ! $classe
            || $classe->ordem > 12
            || (
                $periodo === 3
                && (
                    in_array($turmaAluno->resultado, ['recurso', 'aprovado_recurso', 'reprovado_recurso'], true)
                    || in_array($turmaAluno->situacao, ['recurso', 'aprovado_recurso', 'reprovado_recurso'], true)
                    || $turmaAluno->notas->contains(fn (Nota $nota): bool => $nota->periodo === 4)
                )
            )
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

        foreach ($disciplinasCurriculares as $disciplinaCurricular) {
            $turmaDisciplinaProfessor = $turmaDisciplinasProfessor->get($disciplinaCurricular->id);

            if (! $turmaDisciplinaProfessor) {
                return null;
            }

            $statusPorPeriodo = $turmaDisciplinaProfessor->pautaStatuses->keyBy('periodo');

            if (! in_array($statusPorPeriodo->get($periodo)?->status, ['finalizada', 'expirada'], true)) {
                return null;
            }

            $notas = $turmaAluno->notas
                ->where('turma_disciplina_professor_id', $turmaDisciplinaProfessor->id)
                ->keyBy('periodo');
            $notaTrimestral = $notas->get($periodo);
            $mediaTrimestral = $notaTrimestral?->media_trimestral;

            if ($mediaTrimestral === null) {
                return null;
            }

            $notasPorDisciplina[] = (float) $mediaTrimestral;
            $faltasTotal += (int) ($notaTrimestral->faltas ?? 0);
            $notasAbaixoDez += (int) ($mediaTrimestral < Nota::NOTA_MINIMA_APTO);
        }

        $media = $this->avaliarMedia->calcular($notasPorDisciplina, $mediaMinima);

        if ($media === null) {
            return null;
        }

        $curso = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome;
        $nomeClasse = $classe->nome;
        $nomeAluno = $aluno->inscricao?->candidato?->nome;

        if (! $curso || ! $nomeClasse || ! $nomeAluno) {
            return null;
        }

        return [
            'turma_aluno_id' => $turmaAluno->id,
            'aluno_id' => $aluno->id,
            'nome_aluno' => $nomeAluno,
            'matricula' => $aluno->matricula,
            'classe' => $nomeClasse,
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
