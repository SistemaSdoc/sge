<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\CursoClasseRecord;
use App\Models\CursoClasseTurno;
use App\Models\PautaStatus;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;

class PreencherHistoricoService
{
    /**
     * Retorna classes que o aluno ainda precisa de histórico.
     * Mostra:
     * - "Lançar Notas" para classes sem turma_aluno
     * - "Continuar" para classes com notas não finalizadas
     * - Oculta classes com lançamento completo e finalizado
     */
    public function obterClassesFaltando(Aluno $aluno): array
    {
        $inscricao = $aluno->inscricao?->loadMissing([
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $cursoClasseActual = $inscricao?->cursoClasseTurno?->cursoClasse;
        $ordemActual = $cursoClasseActual?->classe?->ordem;
        $cursoTuteladoId = $cursoClasseActual?->curso_tutelado_id;

        $classes = $cursoTuteladoId
            ? CursoClasseRecord::with('classe')
                ->where('curso_tutelado_id', $cursoTuteladoId)
                ->when(
                    $ordemActual !== null,
                    fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('ordem', '<', $ordemActual))
                )
                ->get()
            : collect();

        if ($classes->isEmpty()) {
            $classes = $this->obterClassesPorTurmaAluno($aluno);
        }

        if ($classes->isEmpty()) {
            return [];
        }

        $turmaAlunos = TurmaAluno::where('aluno_id', $aluno->id)
            ->with([
                'turma.cursoClasseTurno.cursoClasse.classe',
                'notas',   // ← carrega notas para detectar "sem notas"
            ])
            ->get();

        $resultado = [];

        foreach ($classes as $cc) {
            $ta = $turmaAlunos->first(
                fn ($x) => $x->turma?->cursoClasseTurno?->curso_classe_id === $cc->id
            );

            if (! $ta) {
                // Nunca iniciou — mostra botão "Lançar Notas"
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

            $todasFinalizadas = $this->verificarSeTodasPautasFinalizadas($ta->turma->id);

            if ($todasFinalizadas) {
                // Completo — oculta
                continue;
            }

            // turma_aluno existe mas histórico incompleto (com ou sem notas)
            $resultado[] = [
                'curso_classe_id' => $cc->id,
                'classe' => $cc->classe->nome,
                'ordem' => $cc->classe->ordem,
                'turma_aluno_id' => $ta->id,
                'em_curso' => true,
                'tem_notas' => $ta->notas->isNotEmpty(),
            ];
        }

        return collect($resultado)->sortBy('ordem')->values()->toArray();
    }

    private function obterClassesPorTurmaAluno(Aluno $aluno): Collection
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
    private function verificarSeTodasPautasFinalizadas(string $turmaId): bool
    {
        $tdps = TurmaDisciplinaProfessor::where('turma_id', $turmaId)
            ->pluck('id');

        if ($tdps->isEmpty()) {
            return false;
        }

        $numDisciplinas = $tdps->count();

        // Verifica cada período (1, 2, 3)
        for ($periodo = 1; $periodo <= 3; $periodo++) {
            $pautasFinalizadasNestePeriodo = PautaStatus::whereIn('turma_disciplina_professor_id', $tdps)
                ->where('periodo', $periodo)
                ->where('status', 'finalizada')
                ->count();

            // Se não tem o mesmo número de pautas finalizadas que disciplinas,
            // significa que faltam disciplinas neste período
            if ($pautasFinalizadasNestePeriodo !== $numDisciplinas) {
                return false;
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
                $q->where('instituicao_id', $instituicaoId);
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
        $turma = Turma::findOrFail($turmaId);

        if ($turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicao_tutora_id !== $instituicaoId) {
            throw new \Exception('Turma não pertence à sua instituição.');
        }

        // Se já existe com notas, não permite recriar
        $comNotas = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaId)
            ->whereHas('notas')
            ->first();

        if ($comNotas) {
            throw new \Exception('Aluno já tem notas registadas para essa turma.');
        }

        // Se já existe sem notas, reutiliza em vez de duplicar
        $semNotas = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaId)
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
        ]);
    }
}
