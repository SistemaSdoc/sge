<?php

namespace App\Services\Tenant\Pauta;

use App\Models\Tenant\Nota;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use Illuminate\Support\Collection;

/**
 * Obtém as médias finais do aluno na turma do ano lectivo anterior.
 */
final class NotasClasseAnterior
{
    /**
     * @param  list<string>  $alunoIds
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return array<string, array<string, float>>
     */
    public function obter(Turma $turma, array $alunoIds, array $disciplinas): array
    {
        $historico = $this->obterTodas($turma, $alunoIds);

        $turma->loadMissing('cursoClasseTurno.cursoClasse.classe');

        $ordemClasseAnterior = (int) ($turma->cursoClasseTurno?->cursoClasse?->classe?->ordem ?? 0) - 1;

        $notasDaClasseAnterior = [];

        foreach ($historico['alunos'] as $alunoId => $notasPorClasse) {
            foreach ($notasPorClasse[$ordemClasseAnterior] ?? [] as $disciplinaId => $nota) {
                if (\in_array($disciplinaId, array_column($disciplinas, 'id'), true)) {
                    $notasDaClasseAnterior[$alunoId][$disciplinaId] = $nota;
                }
            }
        }

        return $notasDaClasseAnterior;
    }

    /**
     * Obtém as médias finais das matrículas históricas do mesmo curso.
     *
     * @param  list<string>  $alunoIds
     * @return array{disciplinas: array<string, array{id: string}>, alunos: array<string, array<int, array<string, float>>>}
     */
    public function obterTodas(Turma $turma, array $alunoIds): array
    {
        if ($alunoIds === []) {
            return ['disciplinas' => [], 'alunos' => []];
        }

        $turma->loadMissing('cursoClasseTurno.cursoClasse.classe');

        $cursoTuteladoId = $turma->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;

        $ordemClasseActual = $turma->cursoClasseTurno?->cursoClasse?->classe?->ordem;

        if ($cursoTuteladoId === null || $ordemClasseActual === null) {
            return ['disciplinas' => [], 'alunos' => []];
        }

        $matriculasAnteriores = TurmaAluno::query()
            ->with([
                'turma.cursoClasseTurno.cursoClasse.classe',
                'notas' => fn ($query) => $query->whereIn('periodo', [1, 2, 3]),
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina',
            ])
            ->whereIn('aluno_id', $alunoIds)
            ->whereHas('turma.cursoClasseTurno.cursoClasse', function ($query) use (
                $cursoTuteladoId,
                $ordemClasseActual
            ): void {
                $query->where('curso_tutelado_id', $cursoTuteladoId)
                    ->whereHas('classe', fn ($classeQuery) => $classeQuery->where('ordem', '<', $ordemClasseActual));
            })
            ->orderBy('created_at')
            ->get();

        $disciplinasHistoricas = [];

        $notasPorAluno = [];

        foreach ($matriculasAnteriores as $matriculaAnterior) {
            $alunoId = (string) $matriculaAnterior->aluno_id;

            $ordemClasse = (int) $matriculaAnterior->turma?->cursoClasseTurno?->cursoClasse?->classe?->ordem;

            foreach ($matriculaAnterior->notas->groupBy(
                fn (Nota $nota): ?string => $nota->turmaDisciplinaProfessor?->classeTurnoDisciplina?->disciplina_id,
            ) as $disciplinaId => $notasDisciplina) {
                if ($disciplinaId === '') {
                    continue;
                }

                $notaFinal = $this->calcularMediaFinal($notasDisciplina);

                if ($notaFinal === null) {
                    continue;
                }

                $disciplinaId = (string) $disciplinaId;

                $disciplinasHistoricas[$disciplinaId] = ['id' => $disciplinaId];

                $notasPorAluno[$alunoId][$ordemClasse][$disciplinaId] = $notaFinal;
            }
        }

        return [
            'disciplinas' => $disciplinasHistoricas,
            'alunos' => $notasPorAluno,
        ];
    }

    /**
     * Calcula a média anual com as mesmas regras de precisão da pauta final.
     *
     * @param  Collection<int, Nota>  $notas
     */
    private function calcularMediaFinal(Collection $notas): ?float
    {
        $mediaFinalRegistada = $notas->first(fn (Nota $nota): bool => $nota->media_final !== null)?->media_final;

        if ($mediaFinalRegistada !== null) {
            return (float) $mediaFinalRegistada;
        }

        $mediasTrimestrais = collect([1, 2, 3])
            ->map(fn (int $periodo): ?float => $notas->firstWhere('periodo', $periodo)?->media_trimestral);

        if ($mediasTrimestrais->contains(fn (?float $media): bool => $media === null)) {
            return null;
        }

        return (float) round(
            round($mediasTrimestrais->avg(), 1, PHP_ROUND_HALF_UP),
            0,
            PHP_ROUND_HALF_UP,
        );
    }
}
