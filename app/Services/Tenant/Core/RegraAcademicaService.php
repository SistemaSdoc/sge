<?php

namespace App\Services\Tenant\Core;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademica\Contexto\Contexto;
use App\Services\Tenant\Core\RegraAcademica\Disciplina\Disciplina;
use App\Services\Tenant\Core\RegraAcademica\Recurso\Recurso;
use App\Services\Tenant\Core\RegraAcademica\RegraAplicavel\RegraAplicavel;
use App\Services\Tenant\Core\RegraAcademica\Resultado\Resultado;

class RegraAcademicaService
{
    public function __construct(
        private readonly Contexto $contexto,
        private readonly RegraAplicavel $regraAplicavel,
        private readonly Disciplina $disciplina,
        private readonly Resultado $resultado,
        private readonly Recurso $recurso,
    ) {}

    /**
     * Resolve a situação académica final para um aluno.
     *
     * @param  TurmaAluno  $turmaAluno  Aluno e relações da turma que vão ser avaliadas.
     * @return array<string, mixed> Resultado global com situação, mensagem e detalhes das disciplinas.
     */
    public function resolverSituacaoAcademica(TurmaAluno $turmaAluno): array
    {
        $contexto = $this->contexto->forAluno($turmaAluno);

        $notasTrimestrais = $turmaAluno->notas
            ->whereIn('periodo', [1, 2, 3]);
        $notasPorDisciplina = $notasTrimestrais
            ->groupBy('turma_disciplina_professor_id');
        $notasPeriodo3 = $notasTrimestrais
            ->where('periodo', 3);

        $disciplinasEsperadas = $turmaAluno->turma
            ->turmaDisciplinaProfessor
            ->pluck('id')
            ->unique()
            ->values();

        if ($disciplinasEsperadas->isEmpty()) {
            return $this->resultado->construir(
                'incompleto',
                'Aluno não tem disciplinas configuradas para avaliação.',
                []
            );
        }

        $disciplinasPendentes = $disciplinasEsperadas
            ->filter(function (string $turmaDisciplinaProfessorId) use ($notasPorDisciplina): bool {
                $notas = $notasPorDisciplina->get($turmaDisciplinaProfessorId, collect());

                return collect([1, 2, 3])->contains(
                    fn (int $periodo): bool => $notas->firstWhere('periodo', $periodo)?->media_trimestral === null
                );
            })
            ->count();

        if ($disciplinasPendentes > 0) {
            return $this->resultado->construir(
                'incompleto',
                "Aluno tem {$disciplinasPendentes} disciplina(s) com notas pendentes.",
                []
            );
        }

        $notasFinais = $notasPeriodo3;

        // ── EEF ───────────────────────────────────────────────────

        $temEEF = $turmaAluno->notas
            ->whereIn('periodo', [1, 2, 3])
            ->contains(fn ($n) => $n->situacao_trimestral === 'EEF');

        if ($temEEF) {
            return $this->resultado->construir('EEF', 'Aluno reprovado por faltas.', []);
        }

        // ── Contexto ──────────────────────────────────────────────

        $classeActual = $contexto['classe_actual'];
        $ehUltimaClasse = $contexto['eh_ultima_classe'];
        $disciplinasProximaClasse = $contexto['disciplinas_proxima_classe'];

        // ── Regra de avaliação ────────────────────────────────────

        $regra = $this->regraAplicavel->resolve($turmaAluno, $classeActual->id);

        $notaMinima = $regra?->media_minima_aprovacao ?? Nota::NOTA_MINIMA_APTO;
        $permiteRecurso = $regra?->permite_recurso ?? true;
        $maxNegativas = $regra?->max_disciplinas_negativas; // pode ser null

        // ── Verificar frequência global (se a regra tiver) ──────────
        // (Opcional: se quiser usar a frequencia_minima da regra)
        // $frequenciaMinima = $regra?->frequencia_minima ?? 75;
        // ... lógica de frequência global ...

        // ── Avaliar disciplinas ───────────────────────────────────

        $detalhes = [];
        $disciplinasNegativas = 0; // contador

        foreach ($notasFinais as $nota) {

            $disciplina = $nota->turmaDisciplinaProfessor
                ?->classeTurnoDisciplina
                ?->disciplina;

            if (! $disciplina) {
                continue;
            }

            $mediaFinal = $this->mediaFinalBase($turmaAluno, $nota);

            $avaliacao = $this->disciplina->avaliar(
                disciplinaId: $disciplina->id,
                mediaFinal: $mediaFinal,
                notaMinima: $notaMinima,
                ehUltimaClasse: $ehUltimaClasse,
                permiteRecurso: $permiteRecurso,
                disciplinasProximaClasse: $disciplinasProximaClasse,
            );

            if ($avaliacao['negativa']) {
                $disciplinasNegativas++;
            }

            $detalhes[] = [
                'disciplina_id' => $disciplina->id,
                'disciplina' => $disciplina->nome,
                'media_final' => $mediaFinal,
                'situacao' => $avaliacao['situacao'],
                'continua' => $avaliacao['continua'],
            ];
        }

        // ── Verificar limite de negativas ─────────────────────────
        // Se o limite estiver definido e o número de negativas ultrapassá-lo,
        // o aluno é reprovado automaticamente.

        if ($maxNegativas !== null && $disciplinasNegativas > $maxNegativas) {
            return $this->resultado->construir(
                'reprovado_negativas',
                "Reprovado por excesso de disciplinas negativas ({$disciplinasNegativas} > {$maxNegativas}).",
                $detalhes
            );
        }

        // ── Situação global (comportamento original) ────────────────

        $resolucao = $this->resultado->resolver(collect($detalhes));

        return $this->resultado->construir(
            $resolucao['situacao'],
            $resolucao['mensagem'],
            $detalhes,
        );
    }

    /**
     * Resolve a situação de recurso depois da avaliação académica principal.
     *
     * @param  TurmaAluno  $turmaAluno  Aluno cuja nota de recurso vai ser processada.
     * @return array<string, mixed> Resultado final após a análise do recurso.
     */
    public function resolverSituacaoRecurso(TurmaAluno $turmaAluno): array
    {
        $avaliacaoRecurso = $this->recurso->avaliar(
            turmaAluno: $turmaAluno,
        );

        return $this->resultado->construir(
            $avaliacaoRecurso['situacao'],
            $avaliacaoRecurso['mensagem'],
            $avaliacaoRecurso['detalhes'],
        );
    }

    private function mediaFinalBase(TurmaAluno $turmaAluno, Nota $notaPeriodo3): float
    {
        $notasDisciplina = $turmaAluno->notas
            ->where('turma_disciplina_professor_id', $notaPeriodo3->turma_disciplina_professor_id)
            ->whereIn('periodo', [1, 2, 3]);

        $mediasTrimestrais = collect([1, 2, 3])
            ->map(fn (int $periodo) => $notasDisciplina->firstWhere('periodo', $periodo)?->media_trimestral);

        return round($mediasTrimestrais->avg(), 1, PHP_ROUND_HALF_UP);
    }
}
