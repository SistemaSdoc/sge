<?php

namespace App\Services\Core;

use App\Models\Nota;
use App\Models\TurmaAluno;
use App\Models\CursoClasse;
use Illuminate\Support\Collection;

class RegraAcademicaService
{
    // ─────────────────────────────────────────────
    // PONTO PRINCIPAL
    // ─────────────────────────────────────────────

    public function avaliarAluno(TurmaAluno $turmaAluno): array
    {
        $turmaAluno->loadMissing([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        // Apenas notas finais (3º trimestre)
        $notasFinais = $turmaAluno->notas
            ->where('periodo', 3)
            ->whereNotNull('media_final');

        // ─────────────────────────────────────────
        // 1. Verificar EEF
        // ─────────────────────────────────────────

        $temEEF = $turmaAluno->notas
            ->whereIn('periodo', [1, 2, 3])
            ->contains(fn($n) => $n->situacao_trimestral === 'EEF');

        if ($temEEF) {
            return $this->resultado(
                'EEF',
                'Aluno reprovado por faltas.',
                []
            );
        }

        // ─────────────────────────────────────────
        // 2. Dados da próxima classe
        // ─────────────────────────────────────────

        $classeActual = $turmaAluno->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->classe;

        $cursoTutelado = $turmaAluno->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado;

        $disciplinasProximaClasse = $this->getDisciplinasProximaClasse(
            $cursoTutelado->id,
            $classeActual->ordem
        );

        $ehUltimaClasse = is_null($disciplinasProximaClasse);

        // ─────────────────────────────────────────
        // 3. Avaliar disciplinas
        // ─────────────────────────────────────────

        $detalhes = [];

        foreach ($notasFinais as $nota) {

            $disciplina = $nota->turmaDisciplinaProfessor
                ?->classeTurnoDisciplina
                    ?->disciplina;

            if (!$disciplina) {
                continue;
            }

            $mediaFinal = (float) $nota->media_final;

            // ─────────────────────────────────────
            // Disciplina aprovada
            // ─────────────────────────────────────

            if ($mediaFinal >= Nota::NOTA_MINIMA_APTO) {

                $detalhes[] = [
                    'disciplina_id' => $disciplina->id,
                    'disciplina' => $disciplina->nome,
                    'media_final' => $mediaFinal,
                    'situacao' => 'aprovado',
                ];

                continue;
            }

            // ─────────────────────────────────────
            // Disciplina negativa
            // ─────────────────────────────────────

            $continua = $ehUltimaClasse
                ? false
                : $this->disciplinaContinua(
                    $disciplina->id,
                    $disciplinasProximaClasse
                );

            $situacao = match (true) {

                // Última classe → sempre recurso
                $ehUltimaClasse => 'recurso',

                // Disciplina contínua
                $continua => 'transita_com_deficiencia',

                // Disciplina não contínua
                default => 'recurso',
            };

            $detalhes[] = [
                'disciplina_id' => $disciplina->id,
                'disciplina' => $disciplina->nome,
                'media_final' => $mediaFinal,
                'situacao' => $situacao,
                'continua' => $continua,
            ];
        }

        // ─────────────────────────────────────────
        // 4. Situação global
        // ─────────────────────────────────────────

        $temRecurso = collect($detalhes)
            ->contains('situacao', 'recurso');

        $temDeficiencia = collect($detalhes)
            ->contains('situacao', 'transita_com_deficiencia');

        $situacaoGlobal = match (true) {

            $temRecurso => 'recurso',

            $temDeficiencia => 'transita_com_deficiencia',

            default => 'transita',
        };

        $mensagem = match ($situacaoGlobal) {

            'transita' =>
            'Aluno aprovado em todas as disciplinas.',

            'transita_com_deficiencia' =>
            'Aluno transita com deficiência.',

            'recurso' =>
            'Aluno vai ao recurso.',

            'EEF' =>
            'Aluno reprovado por faltas.',
        };

        return $this->resultado(
            $situacaoGlobal,
            $mensagem,
            $detalhes
        );
    }

    // ─────────────────────────────────────────────
    // RECURSO
    // ─────────────────────────────────────────────

    public function avaliarRecurso(TurmaAluno $turmaAluno): array
    {
        $turmaAluno->loadMissing([
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        // ─────────────────────────────────────────
        // Descobrir disciplinas em recurso
        // ─────────────────────────────────────────

        $resultadoFinal = $this->avaliarAluno($turmaAluno);

        $disciplinasRecurso = collect($resultadoFinal['detalhes'])
            ->where('situacao', 'recurso')
            ->pluck('disciplina_id');

        // ─────────────────────────────────────────
        // Filtrar apenas notas de recurso válidas
        // ─────────────────────────────────────────

        $notasRecurso = $turmaAluno->notas
            ->where('periodo', 4)
            ->filter(function ($nota) use ($disciplinasRecurso) {

                $disciplinaId = $nota->turmaDisciplinaProfessor
                    ?->classeTurnoDisciplina
                        ?->disciplina_id;

                return $disciplinasRecurso->contains($disciplinaId);
            });

        // ─────────────────────────────────────────
        // Sem notas lançadas
        // ─────────────────────────────────────────

        if ($notasRecurso->isEmpty()) {

            return $this->resultado(
                'pendente',
                'Notas de recurso ainda não lançadas.',
                []
            );
        }

        // ─────────────────────────────────────────
        // Avaliar notas
        // ─────────────────────────────────────────

        $detalhes = [];

        foreach ($notasRecurso as $nota) {

            $disciplina = $nota->turmaDisciplinaProfessor
                ?->classeTurnoDisciplina
                    ?->disciplina;

            if (!$disciplina) {
                continue;
            }

            $media = (float) $nota->media_trimestral;

            $situacao = $nota->media_trimestral === null
                ? 'pendente'
                : (
                    $media >= Nota::NOTA_MINIMA_APTO
                    ? 'aprovado_recurso'
                    : 'reprovado_recurso'
                );

            $detalhes[] = [
                'disciplina_id' => $disciplina->id,
                'disciplina' => $disciplina->nome,
                'media_recurso' => $nota->media_trimestral,
                'situacao' => $situacao,
            ];
        }

        // ─────────────────────────────────────────
        // Resultado global
        // ─────────────────────────────────────────

        $temPendente = collect($detalhes)
            ->contains('situacao', 'pendente');

        $temReprovado = collect($detalhes)
            ->contains('situacao', 'reprovado_recurso');

        $situacaoGlobal = match (true) {

            $temPendente => 'pendente',

            $temReprovado => 'reprovado_recurso',

            default => 'aprovado_recurso',
        };

        $mensagem = match ($situacaoGlobal) {

            'pendente' =>
            'Recurso ainda não concluído.',

            'reprovado_recurso' =>
            'Aluno reprovado no recurso.',

            default =>
            'Aluno aprovado no recurso.',
        };

        return $this->resultado(
            $situacaoGlobal,
            $mensagem,
            $detalhes
        );
    }
    // ─────────────────────────────────────────────
    // DISCIPLINAS DA PRÓXIMA CLASSE
    // ─────────────────────────────────────────────

    private function getDisciplinasProximaClasse(
        string $cursoTuteladoId,
        int $ordemClasseActual
    ): ?Collection {

        $proximaCursoClasse = CursoClasse::with(
            'turnos.classeTurnoDisciplinas'
        )
            ->whereHas(
                'classe',
                fn($q) => $q->where(
                    'ordem',
                    $ordemClasseActual + 1
                )
            )
            ->where(
                'curso_tutelado_id',
                $cursoTuteladoId
            )
            ->first();

        if (!$proximaCursoClasse) {
            return null;
        }

        $disciplinas = collect();

        foreach ($proximaCursoClasse->turnos as $turno) {

            foreach ($turno->classeTurnoDisciplinas as $ctd) {

                if ($ctd->disciplina_id) {
                    $disciplinas->push($ctd->disciplina_id);
                }
            }
        }

        return $disciplinas->unique()->values();
    }

    // ─────────────────────────────────────────────
    // VERIFICA CONTINUIDADE
    // ─────────────────────────────────────────────

    private function disciplinaContinua(
        string $disciplinaId,
        Collection $disciplinasProximaClasse
    ): bool {
        return $disciplinasProximaClasse->contains($disciplinaId);
    }

    // ─────────────────────────────────────────────
    // RESULTADO PADRONIZADO
    // ─────────────────────────────────────────────

    private function resultado(
        string $situacao,
        string $mensagem,
        array $disciplinas
    ): array {
        $acao = match ($situacao) {
            'transita', 'transita_com_deficiencia' => 'TRANSITAR',
            'recurso' => 'AGUARDAR_RECURSO',
            'aprovado_recurso' => 'TRANSITAR',      // ← adicionar
            'reprovado_recurso' => 'RETER',          // ← adicionar
            'reprovado', 'EEF' => 'RETER',
            default => 'INCOMPLETO',
        };

        return [
            'resultado' => $situacao,
            'situacao' => $situacao,
            'acao' => $acao,
            'mensagem' => $mensagem,
            'disciplinas' => $disciplinas,
            'detalhes' => $disciplinas,
        ];
    }

    // Substitui calcularResultadoFinalAluno por um alias limpo se precisares:
    public function calcularResultadoFinalAluno(TurmaAluno $turmaAluno): array
    {
        return $this->avaliarAluno($turmaAluno); // delega para o método correcto
    }
}