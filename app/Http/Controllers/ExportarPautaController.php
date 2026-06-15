<?php

namespace App\Http\Controllers;

use App\Exports\PautaExport;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportarPautaController extends Controller
{
    public function exportarExcel(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Request $request
    ) {
        $periodo = $request->query('periodo');

        // ── Subir a cadeia completa a partir da Turma ──────────
        $turma->loadMissing([
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $instituicaoCurso = $turma->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso;

        $instituicao = $instituicaoCurso?->instituicao;
        $curso = $instituicaoCurso?->curso;
        $classeNome = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

        // ── 1. DISCIPLINAS ─────────────────────────────────────
        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        $disciplinas = $tdps->map(fn ($tdp) => [
            'id' => $tdp->classeTurnoDisciplina->disciplina->id,
            'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
            'tdp_id' => $tdp->id,
        ]);

        $nomesDisciplinas = $disciplinas->pluck('nome')->toArray();

        // ── 2. ALUNOS + NOTAS ──────────────────────────────────
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $periodo
                ? $q->where('periodo', $periodo)
                : $q->whereNotNull('media_final'),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->get();

        $alunos = $turmaAlunos->map(function ($ta, $index) use ($disciplinas, $periodo) {
            $notasPorTdp = $ta->notas->keyBy('turma_disciplina_professor_id');

            $notasMapeadas = $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp, $periodo) {
                $nota = $notasPorTdp->get($disc['tdp_id']);

                return [
                    $disc['nome'] => $nota ? [
                        'media' => (float) ($periodo ? $nota->media_trimestral : $nota->media_final),
                        'faltas' => (int) $nota->faltas,
                    ] : null,
                ];
            })->toArray();

            $totalFaltas = collect($notasMapeadas)->sum(fn ($n) => $n['faltas'] ?? 0);
            $disciplinasAbaixo = collect($notasMapeadas)->filter(fn ($n) => $n && $n['media'] < 10)->count();

            $resultado = match (true) {
                $totalFaltas > 10,
                $disciplinasAbaixo >= 4 => 'N/APTO',
                default => 'APTO',
            };

            return [
                'numero' => $index + 1,
                'nome' => $ta->aluno->inscricao?->candidato?->nome ?? '',
                'notas' => $notasMapeadas,
                'total_faltas' => $totalFaltas,
                'resultado' => $resultado,
            ];
        })->toArray();

        // ── 3. EXPORT ──────────────────────────────────────────
        $export = new PautaExport(
            disciplinas: $nomesDisciplinas,
            alunos: $alunos,
            curso: $curso?->nome ?? '',
            turma: $turma->nome ?? '',
            anoLetivo: $turma->ano_letivo ?? date('Y').'/'.(date('Y') + 1),
            instituicao: $instituicao?->nome ?? 'INSTITUTO',
            sala: $turma->sala ?? '',
            classe: $classeNome,
            periodo: $periodo ?? 'final',
        );

        $sufixo = $periodo ? "_{$periodo}trim" : '_final';
        $filename = 'pauta_'.str($turma->nome)->slug().$sufixo.'.xlsx';

        return Excel::download($export, $filename);
    }
}
