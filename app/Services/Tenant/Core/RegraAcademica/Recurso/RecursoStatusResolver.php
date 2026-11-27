<?php

namespace App\Services\Tenant\Core\RegraAcademica\Recurso;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use Illuminate\Support\Collection;

/**
 * Resolve o estado global do recurso com base nas notas lançadas para o aluno.
 */
class RecursoStatusResolver
{
    /**
     * Resolve o estado final do recurso para as disciplinas em análise.
     */
    public function resolver(TurmaAluno $turmaAluno): array
    {
        $disciplinasRecurso = $turmaAluno->notas
            ->whereIn('periodo', [1, 2, 3])
            ->groupBy('turma_disciplina_professor_id')
            ->map(function (Collection $notas): ?array {
                $mediasTrimestrais = collect([1, 2, 3])
                    ->map(fn (int $periodo) => $notas->firstWhere('periodo', $periodo)?->media_trimestral);

                if ($mediasTrimestrais->contains(fn ($media): bool => $media === null)) {
                    return null;
                }

                $mediaFinal = round($mediasTrimestrais->avg(), 1, PHP_ROUND_HALF_UP);

                if ($mediaFinal >= Nota::NOTA_MINIMA_APTO) {
                    return null;
                }

                $disciplina = $notas->first()
                    ?->turmaDisciplinaProfessor
                    ?->classeTurnoDisciplina
                    ?->disciplina;

                if (! $disciplina) {
                    return null;
                }

                return [
                    'turma_disciplina_professor_id' => $notas->first()->turma_disciplina_professor_id,
                    'disciplina_id' => $disciplina->id,
                    'disciplina' => $disciplina->nome,
                    'media_final' => $mediaFinal,
                ];
            })
            ->filter();

        $notasRecurso = $turmaAluno->notas
            ->where('periodo', '=', 4)
            ->keyBy('turma_disciplina_professor_id');

        if ($disciplinasRecurso->isEmpty()) {
            return [
                'situacao' => 'pendente',
                'mensagem' => 'Não existem disciplinas elegíveis para recurso.',
                'detalhes' => [],
            ];
        }

        $notaMinima = Nota::NOTA_MINIMA_APTO;

        $detalhes = $disciplinasRecurso->map(function (array $disciplinaRecurso) use ($notasRecurso, $notaMinima): array {
            $nota = $notasRecurso->get($disciplinaRecurso['turma_disciplina_professor_id']);
            $media = $nota?->media_trimestral;

            $situacao = match (true) {
                ! $nota || is_null($media) => 'pendente',
                (float) $media >= $notaMinima => 'aprovado_recurso',
                default => 'reprovado_recurso',
            };

            return [
                'disciplina_id' => $disciplinaRecurso['disciplina_id'],
                'disciplina' => $disciplinaRecurso['disciplina'],
                'media_final' => $disciplinaRecurso['media_final'],
                'media_recurso' => $media,
                'situacao' => $situacao,
            ];
        });

        return $this->normalizar($detalhes);
    }

    /**
     * Constrói a resposta global do recurso a partir dos detalhes por disciplina.
     */
    private function normalizar(Collection|array $detalhes): array
    {
        $col = collect($detalhes);

        $situacaoGlobal = match (true) {
            $col->contains('situacao', 'pendente') => 'pendente',
            $col->contains('situacao', 'reprovado_recurso') => 'reprovado_recurso',
            default => 'aprovado_recurso',
        };

        $mensagem = match ($situacaoGlobal) {
            'pendente' => 'Recurso ainda não concluído.',
            'reprovado_recurso' => 'Aluno reprovado no recurso.',
            default => 'Aluno aprovado no recurso.',
        };

        return [
            'situacao' => $situacaoGlobal,
            'mensagem' => $mensagem,
            'detalhes' => $col->all(),
        ];
    }
}
