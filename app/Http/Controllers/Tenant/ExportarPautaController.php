<?php

namespace App\Http\Controllers\Tenant;

use App\Exports\PautaExport;
use App\Exports\PautaFinalExport;
use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\User;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExportarPautaController extends Controller
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {}

    public function exportarExcel(
        string $cursoTutelado,
        string $turma,
        Request $request,
        bool $resolveShared = true,
        bool $remoteTutor = false,
        ?User $authorizedUser = null,
    ) {
        /** @var User $user */
        $user = $authorizedUser ?? Auth::guard('tenant')->user();

        if ($resolveShared) {
            $shared = CursoTuteladoShared::query()
                ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
                ->where('curso_tutelado_tutelado_id', $cursoTutelado)
                ->where('status', 'activo')
                ->first();

            if ($shared) {
                if ($user?->hasRole('Secretario do Curso')) {
                    abort_unless(
                        $user->instituicao?->tipo === 'instituto'
                            && $user->cursosSecretariados()
                                ->whereHas('instituicaoCurso', fn ($query) => $query->where('curso_id', $shared->curso_id))
                                ->exists(),
                        404,
                    );
                }

                abort_unless($user?->can('pautas.view'), 403);

                return Tenant::findOrFail($shared->tenant_tutelado_id)
                    ->run(fn () => $this->exportarExcel($cursoTutelado, $turma, $request, false, true, $user));
            }
        }

        $cursoTutelado = CursoTutelado::findOrFail($cursoTutelado);
        $turma = Turma::findOrFail($turma);

        if (! $remoteTutor) {
            $this->authorize('pauta.view', $turma);
        } elseif ($user?->hasRole('Secretario do Curso')) {
            abort_unless(
                (string) $turma->cursoClasseTurno?->cursoClasse?->curso_tutelado_id === (string) $cursoTutelado->getKey(),
                404,
            );
        }

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

        $directorDaInstituicao = $instituicaoCurso?->instituicao
            ?->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Director'))
            ->first();
        $nomeDirector = $directorDaInstituicao?->nome ?? $user?->nome ?? 'Director';

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
                areaFormacao: $nomeCurso,
                director: $nomeDirector,
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
                areaFormacao: $nomeCurso,
                director: $nomeDirector,
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
            : $this->resolverResultadoFinal(
                $this->regraAcademicaService->resolverSituacaoAcademica($ta),
                $disciplinas,
            );

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

        $mediasTrimestrais = collect([
            $t1?->media_trimestral,
            $t2?->media_trimestral,
            $t3?->media_trimestral,
        ]);
        $mf = $mediasTrimestrais->contains(fn ($media): bool => $media === null)
            ? null
            : round($mediasTrimestrais->avg(), 1, PHP_ROUND_HALF_UP);

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
     * Resolve o resultado final sem depender do estado alterado pelas notas de recurso.
     */
    private function resolverResultadoFinal(array $resultadoAcademico, Collection $disciplinas): string
    {
        $detalhes = collect($resultadoAcademico['detalhes'] ?? []);

        return match ($resultadoAcademico['situacao'] ?? 'incompleto') {
            'transita' => 'TRANSITA',
            'transita_com_deficiencia' => $this->resultadoComDisciplinas(
                'TRANSITA COM DEFICIÊNCIA',
                $detalhes,
                $disciplinas,
                'transita_com_deficiencia',
            ),
            'recurso' => $this->resultadoComDisciplinas(
                'RECURSO',
                $detalhes,
                $disciplinas,
                'recurso',
            ),
            'EEF' => 'EEF',
            'reprovado', 'reprovado_negativas' => 'N/TRANSITA',
            default => 'INCOMPLETO',
        };
    }

    private function resultadoComDisciplinas(
        string $resultado,
        Collection $detalhes,
        Collection $disciplinas,
        string $situacaoDisciplina,
    ): string {
        $siglasDisciplinas = $detalhes
            ->where('situacao', $situacaoDisciplina)
            ->pluck('disciplina_id')
            ->map(function (string $disciplinaId) use ($disciplinas): ?string {
                $disciplina = $disciplinas->firstWhere('id', $disciplinaId);

                if (! $disciplina) {
                    return null;
                }

                return mb_strtoupper($disciplina['sigla'] ?? mb_substr($disciplina['nome'], 0, 4));
            })
            ->filter(fn (?string $sigla): bool => $sigla !== null && $sigla !== '')
            ->unique()
            ->implode(', ');

        return $siglasDisciplinas === ''
            ? $resultado
            : "{$resultado}: {$siglasDisciplinas}";
    }
}
