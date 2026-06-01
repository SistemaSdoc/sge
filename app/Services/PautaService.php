<?php

namespace App\Services;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\Core\RegraAcademicaService;

class PautaService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {
    }

    public function gerarPauta($turma, $periodo): array
    {
        $periodo = (is_numeric($periodo) && (int) $periodo > 0)
            ? (int) $periodo
            : null;

        // ── Disciplinas ────────────────────────────────────────────────────
        $tdps = TurmaDisciplinaProfessor::with(
            'classeTurnoDisciplina.disciplina'
        )
            ->where('turma_id', $turma->id)
            ->get()
            ->unique('classe_turno_disciplina_id');

        $disciplinas = $tdps->map(function ($tdp) {

            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;

            return [
                'id' => $disciplina?->id,
                'sigla' => $disciplina?->sigla,
                'nome' => $disciplina?->nome,
                'tdp_id' => $tdp->id,
            ];
        })->filter(fn($d) => $d['id'] !== null);

        // ── Alunos ─────────────────────────────────────────────────────────
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',

            'notas' => fn($q) => $periodo
                ? $q->where('periodo', $periodo)   // periodo=0, nunca executa correctamente
                : $q->whereIn('periodo', [1, 2, 3, 4]),

            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',

        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)

            // IMPORTANTE:
            // pauta trimestral → só activos
            // pauta final → mostrar histórico também

            ->get();

        // ── Montar pauta ──────────────────────────────────────────────────
        $alunos = $turmaAlunos->map(function ($ta, $index) use ($disciplinas, $periodo) {

            $notasPorTdp = $ta->notas
                ->groupBy('turma_disciplina_professor_id');

            // ==============================================================
            // PAUTA TRIMESTRAL
            // ==============================================================

            if ($periodo) {

                $notas = $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp, $periodo) {

                    $nota = $notasPorTdp
                        ->get($disc['tdp_id'], collect())
                        ->firstWhere('periodo', $periodo);

                    return [
                        $disc['sigla'] => [
                            'media' => $nota?->media_trimestral,
                            'situacao' => $nota?->situacao_trimestral,
                        ]
                    ];
                });

                return [
                    'numero' => $index + 1,
                    'aluno_id' => $ta->aluno->id,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome,
                    'situacao' => $ta->situacao,
                    'notas' => $notas,
                    'resultado' => 'TRIMESTRAL',
                ];
            }

            // ==============================================================
            // PAUTA FINAL
            // ==============================================================

            $resultadoAcademico =
                $this->regraAcademicaService
                    ->calcularResultadoFinalAluno($ta);

            $notas = $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp, $resultadoAcademico) {

                $notasDisciplina = $notasPorTdp
                    ->get($disc['tdp_id'], collect());

                $nota1 = $notasDisciplina->firstWhere('periodo', 1);
                $nota2 = $notasDisciplina->firstWhere('periodo', 2);
                $nota3 = $notasDisciplina->firstWhere('periodo', 3);
                $nota4 = $notasDisciplina->firstWhere('periodo', 4);

                $detalhe = collect($resultadoAcademico['detalhes'])
                    ->firstWhere('disciplina_id', $disc['id']);

                return [

                    $disc['sigla'] => [

                        't1' => $nota1?->media_trimestral,
                        't2' => $nota2?->media_trimestral,
                        't3' => $nota3?->media_trimestral,

                        'mf' => $nota3?->media_final,

                        'recurso' => $nota4?->media_trimestral,

                        'situacao' => $detalhe['situacao'] ?? null,
                    ]
                ];
            });

            return [

                'numero' => $index + 1,

                'aluno_id' => $ta->aluno->id,

                'nome' => $ta->aluno->inscricao?->candidato?->nome,

                // activo | concluido | recurso etc
                'situacao' => $ta->situacao,

                'notas' => $notas,

                // RESULTADO OFICIAL
                'resultado' => $resultadoAcademico['situacao'],
                'mensagem' => $resultadoAcademico['mensagem'],
            ];
        });

        return [

            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
            ],

            'periodo' => is_null($periodo)
                ? 'final'
                : $periodo,

            'disciplinas' => $disciplinas->pluck('sigla'),

            'resumo' => $periodo
                ? null
                : [

                    'total' => $alunos->count(),

                    'transita' => $alunos
                        ->where('resultado', 'transita')
                        ->count(),

                    'transita_com_deficiencia' => $alunos
                        ->where('resultado', 'transita_com_deficiencia')
                        ->count(),

                    'recurso' => $alunos
                        ->where('resultado', 'recurso')
                        ->count(),

                    'reprovados' => $alunos
                        ->where('resultado', 'reprovado')
                        ->count(),

                    'EEF' => $alunos
                        ->where('resultado', 'EEF')
                        ->count(),
                ],

            'alunos' => $alunos,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // PAUTA DE RECURSO
    // ─────────────────────────────────────────────────────────────────────

    public function gerarPautaRecurso(Turma $turma): array
    {
        // ── Disciplinas da turma ──────────────────────────────────────────────
        $tdps = TurmaDisciplinaProfessor::with(
            'classeTurnoDisciplina.disciplina'
        )
            ->where('turma_id', $turma->id)
            ->get()
            ->unique('classe_turno_disciplina_id');

        $disciplinasPorId = $tdps->mapWithKeys(function ($tdp) {
            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;
            return [
                $disciplina?->id => [
                    'sigla' => $disciplina?->sigla,
                    'tdp_id' => $tdp->id,
                ]
            ];
        })->filter(fn($d) => isset($d['sigla']));

        // ── Buscar todos os activos e filtrar pelos que têm resultado = recurso ──
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->get()
            ->filter(function ($ta) {
                $resultado = $this->regraAcademicaService
                    ->calcularResultadoFinalAluno($ta);
                return $resultado['situacao'] === 'recurso';
            });

        // ── Montar alunos ─────────────────────────────────────────────────────
        $alunos = $turmaAlunos->map(function ($ta, $index) use ($disciplinasPorId) {

            // Avaliação FINAL (período 3) — disciplinas negativas
            $resultadoFinal = $this->regraAcademicaService
                ->avaliarAluno($ta);

            // Avaliação RECURSO (período 4) — nota e situação
            $resultadoRecurso = $this->regraAcademicaService
                ->avaliarRecurso($ta);

            // Disciplinas que ficaram em recurso
            $disciplinasNegativas = collect($resultadoFinal['detalhes'])
                ->where('situacao', 'recurso')
                ->keyBy('disciplina_id');

            // Notas do período 4 agrupadas por tdp_id
            // A nota do recurso é UMA única nota directa (sem mac/npp/npt)
            // guardada em media_trimestral do período 4
            $notasPeriodo4 = $ta->notas
                ->where('periodo', 4)
                ->keyBy('turma_disciplina_professor_id');

            // Montar apenas disciplinas em recurso
            $notas = $disciplinasPorId
                ->filter(fn($d, $discId) => $disciplinasNegativas->has($discId))
                ->mapWithKeys(function ($d, $discId) use ($notasPeriodo4, $disciplinasNegativas, $resultadoRecurso) {

                    $nota4 = $notasPeriodo4->get($d['tdp_id']);
                    $detFinal = $disciplinasNegativas->get($discId);
                    $detRec = collect($resultadoRecurso['detalhes'])
                        ->firstWhere('disciplina_id', $discId);

                    return [
                        $d['sigla'] => [
                            'tdp_id' => $d['tdp_id'],
                            'mf' => $detFinal['media_final'] ?? null,   // média final do período 3
                            'recurso' => $nota4?->media_trimestral,           // única nota do recurso
                            'situacao' => $detRec['situacao'] ?? 'pendente',
                        ]
                    ];
                });

            return [
                'numero' => $index + 1,
                'aluno_id' => $ta->aluno->id,
                'turma_aluno_id' => $ta->id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'notas' => $notas,
                'resultado' => $resultadoRecurso['situacao'] ?? 'pendente',
                'mensagem' => $resultadoRecurso['mensagem'] ?? null,
            ];
        })->values(); // ← values() para reindexar após o filter()

        // ── Disciplinas únicas em recurso (cabeçalho da pauta) ───────────────
        $disciplinasRecurso = $alunos
            ->flatMap(fn($a) => array_keys($a['notas']->toArray()))
            ->unique()
            ->values();

        return [
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
            ],

            'periodo' => 4,
            'tipo' => 'recurso',

            'disciplinas' => $disciplinasRecurso,

            'resumo' => [
                'total' => $alunos->count(),
                'aprovados' => $alunos->where('resultado', 'aprovado_recurso')->count(),
                'reprovados' => $alunos->where('resultado', 'reprovado_recurso')->count(),
                'pendentes' => $alunos->where('resultado', 'pendente')->count(),
            ],

            'alunos' => $alunos,
        ];
    }
}