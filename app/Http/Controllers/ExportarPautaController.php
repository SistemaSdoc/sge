<?php

namespace App\Http\Controllers;

use App\Exports\PautaExport;
use App\Exports\PautaFinalExport;
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
        $periodo = $request->query('periodo'); // '1', '2', '3' ou null (final)
        $isTrimestral = in_array($periodo, ['1', '2', '3'], true);

        // ── Contexto da turma ──────────────────────────────────
        $turma->loadMissing([
            'anoLectivo',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $instituicaoCurso = $turma->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso;

        $nomeInstituicao = $instituicaoCurso?->instituicao?->nome ?? 'INSTITUTO';
        $nomeCurso = $instituicaoCurso?->curso?->nome ?? '';
        $nomeClasse = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';
        $nomeAnoLectivo = $turma->anoLectivo?->nome ?? date('Y').'/'.(date('Y') + 1);

        // ── Disciplinas da turma ───────────────────────────────
        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        $disciplinas = $tdps->map(fn ($tdp) => [
            'id' => $tdp->classeTurnoDisciplina->disciplina->id,
            'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
            'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
            'tdp_id' => $tdp->id,
        ]);

        // ── Alunos e notas ─────────────────────────────────────
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $isTrimestral
                ? $q->where('periodo', $periodo)
                : $q, // final: carrega todos os períodos
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->where('situacao', 'activo')
            ->orderBy('created_at')
            ->get();

        // ── Montar dados dos alunos ────────────────────────────
        $alunos = $turmaAlunos
            ->values()
            ->map(fn ($ta, $index) => $this->montarDadosAluno(
                ta: $ta,
                index: $index,
                disciplinas: $disciplinas,
                isTrimestral: $isTrimestral,
            ))
            ->toArray();

        // ── Exportar ───────────────────────────────────────────────────
        if ($isTrimestral) {
            $export = new PautaExport(
                disciplinas: $disciplinas->map(fn ($d) => [
                    'nome' => $d['nome'],
                    'sigla' => $d['sigla'] ?? mb_substr($d['nome'], 0, 6),
                ])->toArray(),
                alunos: $alunos,
                curso: $nomeCurso,
                turma: $turma->nome,
                anoLetivo: $nomeAnoLectivo,
                instituicao: $nomeInstituicao,
                sala: $turma->sala ?? '',
                classe: $nomeClasse,
                periodo: (string) $periodo,
                areaFormacao: 'INFORMÁTICA',
                director: 'Novais José, Ph.D.',
                logoPath: public_path('images/insignia_angola.png'),
            );
        } else {
            $export = new PautaFinalExport(
                disciplinas: $disciplinas->map(fn ($d) => [
                    'nome' => $d['nome'],
                    'sigla' => $d['sigla'] ?? mb_substr($d['nome'], 0, 4), // fallback
                ])->toArray(),
                alunos: $alunos,
                curso: $nomeCurso,
                turma: $turma->nome,
                anoLetivo: $nomeAnoLectivo,
                instituicao: $nomeInstituicao,
                sala: '',
                classe: $nomeClasse,
                areaFormacao: 'INFORMÁTICA',
                director: 'Novais José, PhD',
                logoPath: public_path('images/insignia_angola.png'),
            );
        }

        $sufixo = $isTrimestral ? "_{$periodo}trim" : '_final';
        $filename = 'pauta_'.str($turma->nome)->slug().$sufixo.'.xlsx';

        return Excel::download($export, $filename);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function montarDadosAluno(
        TurmaAluno $ta,
        int $index,
        $disciplinas,
        bool $isTrimestral,
    ): array {
        $notasPorTdp = $isTrimestral ? $ta->notas->keyBy('turma_disciplina_professor_id') : $ta->notas->groupBy('turma_disciplina_professor_id');

        $notas = $disciplinas->mapWithKeys(
            fn ($disc) => $this->montarNotaDisciplina(
                disc: $disc,
                notasPorTdp: $notasPorTdp,
                isTrimestral: $isTrimestral,
            )
        )->toArray();

        $resultado = $isTrimestral
            ? $this->resolverResultadoTrimestral($notas)
            : $this->resolverResultadoFinal($notas);

        return [
            'numero' => $index + 1,
            'nome' => $ta->aluno->inscricao?->candidato?->nome ?? '',
            'notas' => $notas,
            'total_faltas' => collect($notas)->sum(fn ($n) => $n['faltas'] ?? 0),
            'resultado' => $resultado,
        ];
    }

    private function montarNotaDisciplina(
        array $disc,
        $notasPorTdp,   // agora é groupBy em vez de keyBy para o modo final
        bool $isTrimestral,
    ): array {
        if ($isTrimestral) {
            $nota = $notasPorTdp->get($disc['tdp_id']);
            if (! $nota) {
                return [$disc['nome'] => null];
            }

            return [
                $disc['nome'] => [
                    'media' => $nota->media_trimestral !== null ? (float) $nota->media_trimestral : null,
                    'faltas' => (int) $nota->faltas,
                    'situacao_trimestral' => $nota->situacao_trimestral,
                    'situacao_anual' => $nota->situacao_anual,
                ],
            ];
        }

        // Final: notasPorTdp é groupBy('turma_disciplina_professor_id')
        $linhas = $notasPorTdp->get($disc['tdp_id']);
        if (! $linhas || $linhas->isEmpty()) {
            return [$disc['nome'] => null];
        }

        $p = fn (int $per) => $linhas->firstWhere('periodo', $per);

        $t1 = $p(1);
        $t2 = $p(2);
        $t3 = $p(3);

        // media_final vem de qualquer linha não-nula (é a mesma em todas)
        $mf = collect([$t1, $t2, $t3])
            ->filter()
            ->whereNotNull('media_final')
            ->first()
            ?->media_final;

        return [
            $disc['nome'] => [
                'mt1' => $t1?->media_trimestral !== null ? (float) $t1->media_trimestral : null,
                'mt2' => $t2?->media_trimestral !== null ? (float) $t2->media_trimestral : null,
                'mt3' => $t3?->media_trimestral !== null ? (float) $t3->media_trimestral : null,
                'faltas' => collect([$t1, $t2, $t3])->filter()->sum('faltas'),
                'media_final' => $mf !== null ? (float) $mf : null,
                'situacao_anual' => $t3?->situacao_anual ?? $t2?->situacao_anual ?? $t1?->situacao_anual,
            ],
        ];
    }

    /**
     * Resultado trimestral — lido directamente da situacao_trimestral.
     * EEF tem prioridade sobre N/APTO.
     * Se nenhuma nota foi lançada ainda, devolve vazio.
     */
    private function resolverResultadoTrimestral(array $notas): string
    {
        $total = count($notas);

        $lancadas = collect($notas)
            ->filter(fn ($n) => $n && $n['media'] !== null);

        if ($lancadas->isEmpty()) {
            return '';
        }

        if ($lancadas->count() < $total) {
            return 'INCOMPLETO';
        }

        $mediaGeral = $lancadas->pluck('media')->avg();

        return $mediaGeral >= 10 ? 'APTO' : 'N/APTO';
    }

    /**
     * Resultado final — lido da situacao_anual.
     * EEF tem prioridade sobre N/APTO.
     */
    private function resolverResultadoFinal(array $notas): string
    {
        $lancadas = collect($notas)->filter(
            fn ($n) => $n && $n['situacao_anual'] !== null
        );

        if ($lancadas->isEmpty()) {
            return 'INCOMPLETO';
        }

        return match (true) {
            $lancadas->contains(fn ($n) => $n['situacao_anual'] === 'EEF') => 'EEF',
            $lancadas->contains(fn ($n) => $n['situacao_anual'] === 'N/APTO') => 'N/TRANSITA',
            default => 'TRANSITA',
        };
    }
}
