<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasseRecord;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;

class PreencherHistoricoService
{
    /**
     * Retorna classes que o aluno ainda precisa de histórico.
     * Mostra:
     * - "Iniciar lançamento" para classes sem vínculo histórico
     * - "Lançar Notas" ou "Continuar" para vínculos históricos incompletos
     * - Oculta classes com lançamento completo e finalizado
     */
    public function obterClassesFaltando(Aluno $aluno): array
    {
        $inscricao = $aluno->inscricao?->loadMissing([
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $cursoClasseActual = $inscricao?->cursoClasseTurno?->cursoClasse;
        $ordemActual = $cursoClasseActual?->classe?->ordem
            ?? $aluno->turmaActual()->first()?->cursoClasseTurno?->cursoClasse?->classe?->ordem;

        if ($ordemActual === null) {
            return [];
        }

        $cursoTuteladoId = $cursoClasseActual?->curso_tutelado_id;

        $classes = collect();

        // Mantém apenas classes anteriores à classe actual. Quando existem duas classes,
        // uma que ficou pendente não pode ser escondida por uma outra classe em curso.
        if ($cursoTuteladoId) {
            $classes = CursoClasseRecord::with('classe')
                ->where('curso_tutelado_id', $cursoTuteladoId)
                ->when(
                    $ordemActual !== null,
                    fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('ordem', '<', $ordemActual))
                )
                ->get();
        }

        // Fallback: busca por turmas que o aluno já frequentou
        // (cobre colégios e casos sem tutela directa)
        if ($classes->isEmpty()) {
            $classes = $this->obterClassesPorTurmaAluno($aluno, $ordemActual);
        }

        // Fallback adicional: busca pela instituição do aluno directamente
        // Resolve o caso do colégio sem turma_aluno ainda criada
        if ($classes->isEmpty() && $cursoClasseActual) {
            $instituicaoId = $aluno->instituicao_id
                ?? $aluno->inscricao
                    ?->cursoClasseTurno
                    ?->cursoClasse
                    ?->cursoTutelado
                    ?->instituicaoCurso
                    ?->instituicao_id;

            if ($instituicaoId) {
                $classes = CursoClasseRecord::with('classe')
                    ->whereHas('cursoTutelado', function ($q) use ($instituicaoId) {
                        $q->where('instituicao_tutora_id', $instituicaoId)
                            ->orWhereHas('instituicaoCurso', fn ($q2) => $q2->where('instituicao_id', $instituicaoId));
                    })
                    ->when(
                        $ordemActual !== null,
                        fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('ordem', '<', $ordemActual))
                    )
                    ->get();
            }
        }

        if ($classes->isEmpty()) {
            return [];
        }

        $turmaAlunos = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('is_historico', true)
            ->with([
                'turma.cursoClasseTurno.cursoClasse.classe',
                'notas',
            ])
            ->get();

        $resultado = [];

        foreach ($classes as $cc) {
            $turmaAlunoHistorico = $turmaAlunos->first(
                fn ($turmaAluno) => $turmaAluno->turma?->cursoClasseTurno?->curso_classe_id === $cc->id
            );

            if (! $turmaAlunoHistorico) {
                // Ainda não existe vínculo histórico para continuar.
                $resultado[] = [
                    'curso_classe_id' => $cc->id,
                    'classe' => $cc->classe->nome,
                    'ordem' => $cc->classe->ordem,
                    'turma_aluno_id' => null,
                    'em_curso' => false,
                    'tem_notas' => false,
                ];

                continue;
            }

            $todasFinalizadas = $this->verificarSeTodasPautasFinalizadas(
                $turmaAlunoHistorico->turma->id,
                $turmaAlunoHistorico->id,
            );

            \Log::debug('[Historico] verificacao', [
                'turma_id' => $turmaAlunoHistorico->turma->id,
                'ta_id' => $turmaAlunoHistorico->id,
                'todas_finalizadas' => $todasFinalizadas,
            ]);

            if ($todasFinalizadas) {
                // Completo — oculta
                continue;
            }

            // O vínculo histórico existe, mas ainda tem períodos por finalizar.
            $resultado[] = [
                'curso_classe_id' => $cc->id,
                'classe' => $cc->classe->nome,
                'ordem' => $cc->classe->ordem,
                'turma_aluno_id' => $turmaAlunoHistorico->id,
                'em_curso' => true,
                'tem_notas' => $turmaAlunoHistorico->notas->isNotEmpty(),
            ];
        }

        return collect($resultado)->sortBy('ordem')->values()->toArray();
    }

    private function obterClassesPorTurmaAluno(Aluno $aluno, ?int $ordemActual = null): Collection
    {
        $turmaAlunos = TurmaAluno::where('aluno_id', $aluno->id)
            ->with('turma.cursoClasseTurno.cursoClasse.classe')
            ->get();

        $cursoClasseIds = $turmaAlunos
            ->map(fn ($ta) => $ta->turma?->cursoClasseTurno?->curso_classe_id)
            ->filter()
            ->unique()
            ->values();

        if ($cursoClasseIds->isEmpty()) {
            return collect();
        }

        return CursoClasseRecord::with('classe')
            ->whereIn('id', $cursoClasseIds)
            ->when(
                $ordemActual !== null,
                fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('ordem', '<', $ordemActual))
            )
            ->get();
    }

    /**
     * Verifica se TODAS as pautas de uma turma (todos os trimestres e todas as disciplinas)
     * foram finalizadas.
     *
     * Retorna true apenas se:
     * - Para o período 1: TODAS as disciplinas têm pauta finalizada
     * - Para o período 2: TODAS as disciplinas têm pauta finalizada
     * - Para o período 3: TODAS as disciplinas têm pauta finalizada
     */
    private function verificarSeTodasPautasFinalizadas(string $turmaId, string $turmaAlunoId): bool
    {
        $tdps = TurmaDisciplinaProfessor::where('turma_id', $turmaId)
            ->pluck('id');

        if ($tdps->isEmpty()) {
            return false;
        }

        $numDisciplinas = $tdps->count();

        $isHistorico = TurmaAluno::where('id', $turmaAlunoId)
            ->value('is_historico');

        for ($periodo = 1; $periodo <= 3; $periodo++) {
            if ($isHistorico) {
                // Histórico: verifica notas não rascunho
                $notasFinalizadas = Nota::where('turma_aluno_id', $turmaAlunoId)
                    ->whereIn('turma_disciplina_professor_id', $tdps)
                    ->where('periodo', $periodo)
                    ->where('is_rascunho', false)
                    ->count();

                if ($notasFinalizadas !== $numDisciplinas) {
                    return false;
                }
            } else {
                // Turma normal: lógica original
                $pautasFinalizadas = PautaStatus::whereIn('turma_disciplina_professor_id', $tdps)
                    ->where('periodo', $periodo)
                    ->where('status', 'finalizada')
                    ->count();

                if ($pautasFinalizadas !== $numDisciplinas) {
                    return false;
                }

                $notasAluno = Nota::where('turma_aluno_id', $turmaAlunoId)
                    ->whereIn('turma_disciplina_professor_id', $tdps)
                    ->where('periodo', $periodo)
                    ->whereNotNull('media_trimestral')
                    ->count();

                if ($notasAluno !== $numDisciplinas) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Retorna turnos disponíveis para uma classe.
     */
    public function obterTurnos(string $anoLectivoId, string $cursoClasseId, string $instituicaoId): array
    {
        return CursoClasseTurno::where('curso_classe_id', $cursoClasseId)
            ->with('turno')
            ->distinct()
            ->get()
            ->map(fn ($cct) => [
                'id' => $cct->id,
                'turno_id' => $cct->turno->id,
                'turno_nome' => $cct->turno->nome,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Retorna turmas disponíveis para um classe/turno/ano.
     */
    public function obterTurmas(
        string $anoLectivoId,
        string $cursoClasseTurnoId,
        string $instituicaoId
    ): array {
        return Turma::where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado', function ($q) use ($instituicaoId) {
                $q->where('instituicao_tutora_id', $instituicaoId)
                    ->orWhereHas('instituicaoCurso', fn ($q2) => $q2->where('instituicao_id', $instituicaoId));
            })
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'nome' => $t->nome,
                'max_alunos' => $t->max_alunos,
            ])
            ->toArray();
    }

    /**
     * Cria ou reutiliza TurmaAluno histórico.
     * Nunca cria duplicados — se já existe sem notas, reutiliza.
     * Se já tem notas, lança excepção.
     */
    public function criarTurmaAlunoHistorico(
        Aluno $aluno,
        string $turmaId,
        string $instituicaoId
    ): TurmaAluno {
        $turma = Turma::with('cursoClasseTurno.cursoClasse.cursoTutelado')->findOrFail($turmaId);

        $cursoTutelado = $turma->cursoClasseTurno->cursoClasse->cursoTutelado;

        // Verifica tanto a instituição própria quanto a tutora.
        $pertence = $cursoTutelado->instituicao_tutora_id === $instituicaoId
            || $cursoTutelado->instituicaoCurso?->instituicao_id === $instituicaoId;

        if (! $pertence) {
            throw new \Exception('Turma não pertence à sua instituição.');
        }

        // Se já existe com notas, não permite recriar
        $comNotas = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaId)
            ->where('is_historico', true)
            ->whereHas('notas')
            ->first();

        if ($comNotas) {
            throw new \Exception('Aluno já tem notas registadas para essa turma.');
        }

        // Reutiliza apenas um vínculo histórico sem notas.
        $semNotas = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaId)
            ->where('is_historico', true)
            ->first();

        if ($semNotas) {
            return $semNotas;
        }

        return TurmaAluno::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turmaId,
            'ano_lectivo_id' => $turma->ano_lectivo_id,
            'activo' => true,
            'situacao' => 'concluido',
            'is_historico' => true,
        ]);
    }
}
