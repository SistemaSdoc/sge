<?php

namespace App\Http\Controllers;

use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\AprovacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
    ): Response {
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

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/progressao', [
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
    ): JsonResponse {
        $validated = $request->validate([
            'turma_destino_id' => 'required|exists:turmas,id',
            'ano_lectivo' => 'required|integer|min:2000',
        ]);

        $turmaDestino = Turma::findOrFail(
            $validated['turma_destino_id']
        );

        $anoNovo = (int) $validated['ano_lectivo'];

        $anoAnterior = $anoNovo - 1;

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
        ])
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo', $anoAnterior)
            ->where('activo', true)
            ->get();

        $resultados = [
            'transitados' => [],
            'retidos' => [],
            'recurso' => [],
            'incompletos' => [],
        ];

        DB::transaction(function () use ($turmaAlunos, $turmaDestino, $anoNovo, &$resultados) {

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
                    'TRANSITAR' => (function () use ($ta, $turmaDestino, $anoNovo, $nome, $resultadoFinal, &$resultados) {

                        $this->moverParaProximaClasse(
                            $ta,
                            $turmaDestino->id,
                            $anoNovo
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
                    'AGUARDAR_RECURSO' => (function () use ($nome, $resultadoFinal, &$resultados) {
                        // Aluno continua activo = true, situacao = activo
                        // Apenas regista no array de resultado para controlo
                        $resultados['recurso'][] = [
                            'nome' => $nome,
                            'detalhes' => $resultadoFinal['detalhes'],
                        ];
                    })(),

                    // ─────────────────────────────────────
                    // RETER
                    // ─────────────────────────────────────
                    'RETER' => (function () use ($ta, $anoNovo, $nome, $resultadoFinal, &$resultados) {

                        TurmaAluno::firstOrCreate([
                            'turma_id' => $ta->turma_id,
                            'aluno_id' => $ta->aluno_id,
                            'ano_lectivo' => $anoNovo,
                        ], [
                            'activo' => true,
                            'situacao' => 'activo',
                        ]);

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

        return response()->json([
            'message' => 'Progressão executada com sucesso.',

            'resultados' => $resultados,

            'totais' => [
                'transitados' => count($resultados['transitados']),
                'retidos' => count($resultados['retidos']),
                'recurso' => count($resultados['recurso']),
                'incompletos' => count($resultados['incompletos']),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────

    private function moverParaProximaClasse(
        TurmaAluno $ta,
        string $turmaDestinoId,
        int $anoNovo,
    ): void {

        TurmaAluno::firstOrCreate([
            'turma_id' => $turmaDestinoId,
            'aluno_id' => $ta->aluno_id,
            'ano_lectivo' => $anoNovo,
        ], [
            'activo' => true,
            'situacao' => 'activo',
        ]);

        // encerra histórico anterior
        $ta->update([
            'activo' => false,
            'situacao' => 'concluido',
        ]);
    }

    public function storeRecurso(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
    ): JsonResponse {

        $validated = $request->validate([
            'turma_destino_id' => 'required|exists:turmas,id',
            'ano_lectivo' => 'required|integer|min:2000',
        ]);

        $turmaDestino = Turma::findOrFail($validated['turma_destino_id']);
        $anoNovo = (int) $validated['ano_lectivo'];

        // Apenas alunos em recurso
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
                $resultado = $this->aprovacaoService->calcularAprovacao($ta->id);

                return $resultado['acao'] === 'AGUARDAR_RECURSO';
            });

        $resultados = ['transitados' => [], 'retidos' => [], 'pendentes' => []];

        DB::transaction(function () use ($turmaAlunos, $turmaDestino, $anoNovo, &$resultados) {

            foreach ($turmaAlunos as $ta) {

                $nome = $ta->aluno->inscricao?->candidato?->nome ?? 'Desconhecido';

                // Avalia com base no período 4
                $resultado = $this->aprovacaoService->calcularAprovacaoRecurso($ta->id);

                match ($resultado['situacao']) {

                    'aprovado_recurso' => (function () use ($ta, $turmaDestino, $anoNovo, $nome, &$resultados) {
                        $this->moverParaProximaClasse($ta, $turmaDestino->id, $anoNovo);
                        $resultados['transitados'][] = $nome;
                    })(),

                    'reprovado_recurso' => (function () use ($ta, $anoNovo, $nome, &$resultados) {
                        // Fica retido na mesma turma no novo ano
                        TurmaAluno::firstOrCreate([
                            'turma_id' => $ta->turma_id,
                            'aluno_id' => $ta->aluno_id,
                            'ano_lectivo' => $anoNovo,
                        ], ['activo' => true, 'situacao' => 'activo']);

                        $ta->update(['activo' => false, 'situacao' => 'retido']);
                        $resultados['retidos'][] = $nome;
                    })(),

                    // Notas ainda não lançadas
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

    public function finalizarRecurso(TurmaAluno $ta)
    {
        $resultado = $this->aprovacaoService->calcularAprovacao($ta->id);

        if ($resultado['acao'] === 'TRANSITAR') {
            $this->moverParaProximaClasse(...);
        }

        if ($resultado['acao'] === 'RETER') {
            $ta->update([
                'situacao' => 'retido',
            ]);
        }
    }
}
