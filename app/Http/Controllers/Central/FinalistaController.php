<?php

namespace App\Http\Controllers\Central;

use App\Models\Aluno;
use App\Models\Central\CursoTutelado;
use App\Models\Central\Instituicao;
use App\Models\Central\Turma;
use App\Models\Central\TurmaAluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class FinalistaController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.show', only: ['index', 'historico']),
            new Middleware('permission:turmas.edit', only: ['papConcluido', 'concluir', 'reprovar', 'marcarDesistente']),
        ];
    }*/

    // Lista alunos que defenderam o PAP (pap_concluido)
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma): JsonResponse
    {
        $turma->load([
            'finalistas.inscricao.candidato:id,nome',
            'finalistas.user:id,email',
            'finalistas.grupoPap' => fn ($q) => $q->whereHas(
                'elementos',
                fn ($q) => $q->whereHas('aluno', fn ($q) => $q->whereIn('id', $turma->finalistas->pluck('id')))
            ),
        ]);

        return response()->json([
            'turma' => $turma->nome,
            'finalistas' => $turma->finalistas->map(fn ($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'matricula' => $aluno->matricula,
                'situacao' => $aluno->pivot->situacao,
            ]),
        ]);
    }

    // Marca aluno como pap_concluido após defesa
    public function papConcluido(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        Aluno $aluno
    ): JsonResponse {
        $classe = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome;
        if ($classe !== '13ª') {
            return response()->json([
                'message' => 'Só alunos da 13ª classe podem concluir o PAP.',
            ], 422);
        }

        $turmaAluno = TurmaAluno::where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->firstOrFail();

        if ($turmaAluno->situacao !== 'activo') {
            return response()->json([
                'message' => 'Este aluno já não está activo nesta turma.',
            ], 422);
        }

        DB::transaction(function () use ($turmaAluno, $aluno) {
            $turmaAluno->update(['situacao' => 'pap_concluido']);
            $aluno->update(['situacao' => 'finalista']);
        });

        return response()->json(['message' => 'Aluno marcado como PAP concluído.']);
    }

    // Marca aluno como concluido após certificado gerado
    public function concluir(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        string $aluno
    ): JsonResponse {
        $aluno = Aluno::where('id', $aluno)->firstOrFail();
        $turmaAluno = TurmaAluno::where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->firstOrFail();

        if ($turmaAluno->situacao !== 'pap_concluido') {
            return response()->json([
                'message' => 'O aluno tem de ter o PAP concluído antes de ser marcado como concluído.',
            ], 422);
        }

        DB::transaction(function () use ($turmaAluno, $aluno) {
            // Sai completamente da turma
            $turmaAluno->update([
                'situacao' => 'concluido',
                'activo' => false,
            ]);

            // Estado final no aluno
            $aluno->update(['situacao' => 'concluido']);
        });

        return response()->json(['message' => 'Aluno concluído. Certificado pode ser gerado.']);
    }

    // Marca aluno como reprovado na 13ª (continua na turma)
    public function reprovar(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        Aluno $aluno
    ): JsonResponse {
        $turmaAluno = TurmaAluno::where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->firstOrFail();

        // Mantém na turma mas marca como reprovado
        $aluno->update(['situacao' => 'reprovado']);

        return response()->json(['message' => 'Aluno marcado como reprovado. Repete a 13ª classe.']);
    }

    // Marca aluno como desistente
    public function marcarDesistente(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        Aluno $aluno
    ): JsonResponse {
        $turmaAluno = TurmaAluno::where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->firstOrFail();

        DB::transaction(function () use ($turmaAluno, $aluno) {
            $turmaAluno->update(['activo' => false]);
            $aluno->update(['situacao' => 'desistente']);
        });

        return response()->json(['message' => 'Aluno marcado como desistente.']);
    }

    // Histórico completo do aluno
    public function historico(Instituicao $instituicao, string $aluno): JsonResponse
    {
        $aluno = Aluno::where('id', $aluno)->firstOrFail();
        $aluno->load([
            'inscricao.candidato:id,nome,bi',
            'historicoTurmas.cursoClasseTurno.cursoClasse.classe:id,nome',
            'historicoTurmas.cursoClasseTurno.turno:id,nome',
            'historicoTurmas.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
        ]);

        return response()->json([
            'id' => $aluno->id,
            'nome' => $aluno->inscricao?->candidato?->nome,
            'matricula' => $aluno->matricula,
            'situacao' => $aluno->situacao,
            'historico' => $aluno->historicoTurmas->map(fn ($turma) => [
                'turma' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
                'curso' => $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'ano_lectivo' => $turma->pivot->ano_lectivo,
                'situacao' => $turma->pivot->situacao,
                'notas' => TurmaAluno::where('turma_id', $turma->id)
                    ->where('aluno_id', $aluno->id)
                    ->first()
                    ?->notas()
                    ->with('turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome,sigla')
                    ->get()
                    ->map(fn ($nota) => [
                        'disciplina' => $nota->turmaDisciplinaProfessor?->classeTurnoDisciplina?->disciplina?->nome,
                        'sigla' => $nota->turmaDisciplinaProfessor?->classeTurnoDisciplina?->disciplina?->sigla,
                        'periodo' => $nota->periodo,
                        // 'mac' => $nota->mac,
                        'nota_prova_professor' => $nota->nota_prova_professor,
                        'nota_prova_trimestral' => $nota->nota_prova_trimestral,
                        'media_trimestral' => $nota->media_trimestral,
                        'media_final' => $nota->media_final,
                        // 'faltas' => $nota->faltas,
                        'situacao_trimestral' => $nota->situacao_trimestral,
                        'situacao_anual' => $nota->situacao_anual,
                    ]),
            ]),
        ]);
    }
}
