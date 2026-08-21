<?php

namespace App\Services\Tenant\Core\RegraAcademica\Recurso;

use App\Models\Tenant\Nota;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademica\RegraAplicavel\RegraAplicavel;
use Illuminate\Support\Collection;

/**
 * Resolve o estado global do recurso com base nas notas lançadas para o aluno.
 */
class RecursoStatusResolver
{
    /**
     * Resolve o estado final do recurso para as disciplinas em análise.
     */
    public function resolver(
        TurmaAluno $turmaAluno,
        array $resultadoFinal,
        RegraAplicavel $regraAplicavel,
    ): array {
        $disciplinasRecurso = collect($resultadoFinal['detalhes'])
            ->where('situacao', 'recurso')
            ->pluck('disciplina_id');

        $notasRecurso = $turmaAluno->notas
            ->where('periodo', '=', 4)
            ->filter(fn ($nota) => $disciplinasRecurso->contains(
                $nota->turmaDisciplinaProfessor
                    ?->classeTurnoDisciplina
                    ?->disciplina_id,
            ));

        if ($notasRecurso->isEmpty()) {
            return [
                'situacao' => 'pendente',
                'mensagem' => 'Notas de recurso ainda não lançadas.',
                'detalhes' => [],
            ];
        }

        $classeActual = $turmaAluno->turma->cursoClasseTurno->cursoClasse->classe;
        $regra = $regraAplicavel->resolve($turmaAluno, $classeActual->id);
        $notaMinima = $regra?->nota_minima_recurso ?? $regra?->media_minima_aprovacao ?? Nota::NOTA_MINIMA_APTO;

        $detalhes = [];

        foreach ($notasRecurso as $nota) {
            $disciplina = $nota->turmaDisciplinaProfessor
                ?->classeTurnoDisciplina
                ?->disciplina;

            if (! $disciplina) {
                continue;
            }

            $media = $nota->media_trimestral;

            $situacao = match (true) {
                is_null($media) => 'pendente',
                (float) $media >= $notaMinima => 'aprovado_recurso',
                default => 'reprovado_recurso',
            };

            $detalhes[] = [
                'disciplina_id' => $disciplina->id,
                'disciplina' => $disciplina->nome,
                'media_recurso' => $media,
                'situacao' => $situacao,
            ];
        }

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
