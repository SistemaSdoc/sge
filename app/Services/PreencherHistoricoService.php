<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Classe;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\Turno;

class PreencherHistoricoService
{
    /**
     * Retorna classes que o aluno precisa de histórico.
     * (Classes anteriores à actual sem notas registadas)
     */
    public function obterClassesFaltando(Aluno $aluno): array
    {
        $inscricao = $aluno->inscricao?->loadMissing([
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        if (!$inscricao) {
            return [];
        }

        $cursoClasseActual = $inscricao->cursoClasseTurno?->cursoClasse;

        if (!$cursoClasseActual) {
            return [];
        }

        $ordemActual = $cursoClasseActual->classe->ordem;
        $cursoTuteladoId = $cursoClasseActual->curso_tutelado_id;

        $classesAnteriores = CursoClasse::with('classe')
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn($q) => $q->where('ordem', '<', $ordemActual))
            ->get();

        if ($classesAnteriores->isEmpty()) {
            return [];
        }

        return $classesAnteriores
            ->map(fn($cc) => [
                'curso_classe_id' => $cc->id,
                'classe' => $cc->classe->nome,
                'ordem' => $cc->classe->ordem,
            ])
            ->sortBy('ordem')
            ->values()
            ->toArray();
    }

    /**
     * Retorna turnos disponíveis para uma classe num ano lectivo.
     */
    public function obterTurnos(string $anoLectivoId, string $cursoClasseId, string $instituicaoId): array
    {
        return CursoClasseTurno::where('curso_classe_id', $cursoClasseId)
            ->with('turno')
            ->distinct()
            ->get()
            ->map(fn($cct) => [
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
            ->map(fn($t) => [
                'id' => $t->id,
                'nome' => $t->nome,
                'max_alunos' => $t->max_alunos,
            ])
            ->toArray();
    }

    /**
     * Cria TurmaAluno + redireciona para pauta.
     * Valida se já existe histórico para essa turma.
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

        $jaTem = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaId)
            ->whereHas('notas')
            ->exists();

        if ($jaTem) {
            throw new \Exception('Aluno já tem notas registadas para essa turma.');
        }

        return TurmaAluno::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turmaId,
            'ano_lectivo_id' => $turma->ano_lectivo_id,
            'activo' => false,
            'situacao' => 'concluido',
        ]);
    }
}
