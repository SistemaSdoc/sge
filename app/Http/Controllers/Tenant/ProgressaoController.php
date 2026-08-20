<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\AprovacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProgressaoController extends Controller
{
    public function __construct(
        private readonly AprovacaoService $aprovacaoService,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // PREVIEW
    // ─────────────────────────────────────────────────────────────

    public function preview(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        // Alunos ACTUAIS da turma (no ano da turma)
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->get();

        $resultado = $turmaAlunos->map(function ($ta) {

            $resultadoFinal = $this->aprovacaoService
                ->calcularAprovacao($ta->id);

            return [
                'aluno_id' => $ta->aluno_id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'matricula' => $ta->aluno->matricula,

                'situacao' => $resultadoFinal['situacao'],
                'acao' => $resultadoFinal['acao'],
                'mensagem' => $resultadoFinal['mensagem'],
                'detalhes' => $resultadoFinal['detalhes'],
            ];
        });

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/progressao', [
            'turma' => $turma->nome,

            'total' => $resultado->count(),

            'resumo' => [
                'transitar' => $resultado
                    ->where('acao', 'TRANSITAR')
                    ->count(),

                'reter' => $resultado
                    ->where('acao', 'RETER')
                    ->count(),

                'aguardar_recurso' => $resultado
                    ->where('acao', 'AGUARDAR_RECURSO')
                    ->count(),

                'incompleto' => $resultado
                    ->where('acao', 'INCOMPLETO')
                    ->count(),
            ],

            'alunos' => $resultado,
            'anosLectivos' => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),

            'turmas' => Turma::with([
                'cursoClasseTurno.turno',
                'cursoClasseTurno.cursoClasse.classe',
            ])
                ->whereHas('cursoClasseTurno', function ($q) use ($cursoTutelado, $cursoClasse) {
                    $q->whereHas('cursoClasse', function ($q2) use ($cursoTutelado, $cursoClasse) {
                        $q2->where('curso_tutelado_id', $cursoTutelado->id)
                            ->whereHas('classe', function ($q3) use ($cursoClasse) {
                                $q3->where('ordem', $cursoClasse->classe->ordem + 1);
                            });
                    });
                })
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'classe' => $t->cursoClasseTurno?->cursoClasse?->classe?->nome,
                    'turno' => $t->cursoClasseTurno?->turno?->nome,
                ]),
        ]);

    }

    // ─────────────────────────────────────────────────────────────
    // EXECUTAR PROGRESSÃO
    // ─────────────────────────────────────────────────────────────

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        $validated = $request->validate([
            'turma_destino_id' => 'required|exists:turmas,id',
            'ano_lectivo_id' => 'required|exists:ano_lectivos,id',
        ]);

        $turmaDestino = Turma::findOrFail($validated['turma_destino_id']);
        $novoAnoLectivoId = $validated['ano_lectivo_id'];

        // ✅ Alunos ACTUAIS da turma (no ano da turma)
        // Não precisa buscar "ano anterior" — já temos os da turma actual
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->get();

        if ($turmaAlunos->isEmpty()) {
            return back()->withErrors(['turma' => 'Nenhum aluno activo nesta turma.']);
        }

        $resultados = [
            'transitados' => [],
            'retidos' => [],
            'recurso' => [],
            'incompletos' => [],
        ];

        DB::transaction(function () use ($turmaAlunos, $turmaDestino, $novoAnoLectivoId, $turma, &$resultados) {

            foreach ($turmaAlunos as $ta) {

                $nome = $ta->aluno
                    ->inscricao
                    ?->candidato
                    ?->nome ?? 'Desconhecido';

                $resultadoFinal = $this->aprovacaoService
                    ->calcularAprovacao($ta->id);

                $acao = $resultadoFinal['acao'];

                match ($acao) {

                    // ─────────────────────────────────────
                    // TRANSITAR
                    // ─────────────────────────────────────
                    'TRANSITAR' => (function () use ($ta, $turmaDestino, $novoAnoLectivoId, $nome, $resultadoFinal, &$resultados) {

                        $this->moverParaProximaClasse(
                            $ta,
                            $turmaDestino->id,
                            $novoAnoLectivoId
                        );

                        $resultados['transitados'][] = [
                            'nome' => $nome,
                            'situacao' => $resultadoFinal['situacao'],
                            'detalhes' => $resultadoFinal['detalhes'],
                        ];
                    })(),

                    // ─────────────────────────────────────
                    // AGUARDAR RECURSO
                    // ─────────────────────────────────────
                    'AGUARDAR_RECURSO' => (function () use ($ta, $nome, $resultadoFinal, &$resultados) {
                        // Aluno continua activo, marca como aguardando_recurso
                        $ta->update([
                            'situacao' => 'aguardando_recurso',
                        ]);

                        $resultados['recurso'][] = [
                            'nome' => $nome,
                            'detalhes' => $resultadoFinal['detalhes'],
                        ];
                    })(),

                    // ─────────────────────────────────────
                    // RETER
                    // ─────────────────────────────────────
                    'RETER' => (function () use ($ta, $turma, $nome, $resultadoFinal, &$resultados) {

                        // ✅ Cria novo TurmaAluno na mesma turma (repetirá de ano)
                        // Sem tentar adicionar ano_lectivo_id (não existe em turma_aluno)
                        TurmaAluno::create([
                            'turma_id' => $turma->id,
                            'aluno_id' => $ta->aluno_id,
                            'activo' => true,
                            'situacao' => 'retido',
                        ]);

                        // Encerra anterior
                        $ta->update([
                            'activo' => false,
                            'situacao' => 'retido',
                        ]);

                        $resultados['retidos'][] = [
                            'nome' => $nome,
                            'situacao' => $resultadoFinal['situacao'],
                            'detalhes' => $resultadoFinal['detalhes'],
                        ];
                    })(),

                    // ─────────────────────────────────────
                    // INCOMPLETO
                    // ─────────────────────────────────────
                    default => (function () use ($nome, &$resultados) {

                        $resultados['incompletos'][] = $nome;
                    })(),
                };
            }
        });

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/progressao-resultado', [
            'resultado' => [
                'resultados' => $resultados,
            ],
            'turma' => $turma->nome,
            'ano_lectivo' => AnoLectivo::find($novoAnoLectivoId)?->nome,
            'total' => count($turmaAlunos),
            'resumo' => [
                'transitam' => count($resultados['transitados']),
                'recurso' => count($resultados['recurso']),
                'reprovados' => count($resultados['retidos']),
                'incompleto' => count($resultados['incompletos']),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // PROCESSAR RECURSO
    // ─────────────────────────────────────────────────────────────

    public function storeRecurso(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
    ): JsonResponse {

        $validated = $request->validate([
            'turma_destino_id' => 'required|exists:turmas,id',
            'ano_lectivo_id' => 'required|exists:ano_lectivos,id',  // ✅ UUID
        ]);

        $turmaDestino = Turma::findOrFail($validated['turma_destino_id']);
        $novoAnoLectivoId = $validated['ano_lectivo_id'];  // ✅ UUID

        // Apenas alunos em recurso
        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->where('situacao', 'aguardando_recurso')  // ✅ Filtra por situação
            ->get();

        $resultados = [
            'transitados' => [],
            'retidos' => [],
            'pendentes' => [],
        ];

        DB::transaction(function () use ($turmaAlunos, $turmaDestino, $novoAnoLectivoId, $turma, &$resultados) {

            foreach ($turmaAlunos as $ta) {

                $nome = $ta->aluno->inscricao?->candidato?->nome ?? 'Desconhecido';

                // Avalia com base no período 4 (recurso)
                $resultado = $this->aprovacaoService->calcularAprovacaoRecurso($ta->id);

                $acao = $resultado['acao'] ?? 'INCOMPLETO';

                match ($acao) {

                    // ─────────────────────────────────────
                    // APROVADO NO RECURSO
                    // ─────────────────────────────────────
                    'TRANSITAR' => (function () use ($ta, $turmaDestino, $novoAnoLectivoId, $nome, &$resultados) {

                        $this->moverParaProximaClasse(
                            $ta,
                            $turmaDestino->id,
                            $novoAnoLectivoId
                        );

                        $resultados['transitados'][] = $nome;
                    })(),

                    // ─────────────────────────────────────
                    // REPROVADO NO RECURSO
                    // ─────────────────────────────────────
                    'RETER' => (function () use ($ta, $turma, $nome, &$resultados) {

                        // ✅ Cria novo TurmaAluno na mesma turma
                        TurmaAluno::create([
                            'turma_id' => $turma->id,
                            'aluno_id' => $ta->aluno_id,
                            'activo' => true,
                            'situacao' => 'retido',
                        ]);

                        // Encerra anterior
                        $ta->update([
                            'activo' => false,
                            'situacao' => 'reprovado_recurso',
                        ]);

                        $resultados['retidos'][] = $nome;
                    })(),

                    // ─────────────────────────────────────
                    // NOTAS AINDA NÃO LANÇADAS
                    // ─────────────────────────────────────
                    default => (function () use ($nome, &$resultados) {

                        $resultados['pendentes'][] = $nome;
                    })(),
                };
            }
        });

        return response()->json([
            'message' => 'Progressão de recurso executada.',
            'resultados' => $resultados,
            'totais' => [
                'transitados' => count($resultados['transitados']),
                'retidos' => count($resultados['retidos']),
                'pendentes' => count($resultados['pendentes']),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: Mover para próxima classe
    // ─────────────────────────────────────────────────────────────

    private function moverParaProximaClasse(
        TurmaAluno $ta,
        string $turmaDestinoId,
        string $anoLectivoId,
    ): void {

        // ✅ Cria novo TurmaAluno na turma destino
        TurmaAluno::create([
            'turma_id' => $turmaDestinoId,
            'aluno_id' => $ta->aluno_id,
            'activo' => true,
            'situacao' => 'activo',
        ]);

        // Encerra histórico anterior
        $ta->update([
            'activo' => false,
            'situacao' => 'transitado',
        ]);

        // ✅ Atualiza o aluno com o novo ano lectivo
        $ta->update([
            'activo' => false,
            'situacao' => 'transitado',
        ]);
    }
}
