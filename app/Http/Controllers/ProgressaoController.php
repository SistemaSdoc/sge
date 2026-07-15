<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
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

class ProgressaoController extends Controller
{
    public function __construct(
        private readonly AprovacaoService $aprovacaoService,
    ) {
    }

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

            // ← adicionar isto
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
                ->map(fn($t) => [
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
            'ano_lectivo_id' => 'required|exists:anos_lectivos,id',
        ]);

        $turmaDestino = Turma::findOrFail($validated['turma_destino_id']);
        $novoAnoLectivoId = $validated['ano_lectivo_id'];

        // Busca turmas do ano anterior
        $anoLectivoActual = $turma->anoLectivo;
        $anoLectivoAnterior = AnoLectivo::where('nome', $anoLectivoActual->nome - 1)
            ->orWhere('id', '!=', $novoAnoLectivoId)
            ->first();

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas',
        ])
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $anoLectivoAnterior->id)
            ->where('activo', true)
            ->get();

        $resultados = [
            'transitados' => [],
            'retidos' => [],
            'recurso' => [],
            'incompletos' => [],
        ];

        DB::transaction(function () use ($turmaAlunos, $turmaDestino, $novoAnoLectivoId, &$resultados, ) {

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
                    'TRANSITAR' => (function () use ($ta, $turmaDestino, $novoAnoLectivoId, $nome, $resultadoFinal, &$resultados, ) {

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
                    'RETER' => (function () use ($ta, $novoAnoLectivoId, $nome, $resultadoFinal, &$resultados, ) {

                            TurmaAluno::firstOrCreate([
                            'turma_id' => $ta->turma_id,
                            'aluno_id' => $ta->aluno_id,
                            'ano_lectivo_id' => $novoAnoLectivoId,
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
                    default => (function () use ($nome, &$resultados, ) {

                            $resultados['incompletos'][] = $nome;
                        })(),
                };
            }
        });

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/progressao', [
            'resultado' => [
                'resultados' => $resultados,
            ],
            'turma' => $turma->nome,
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
    // HELPER
    // ─────────────────────────────────────────────────────────────

    private function moverParaProximaClasse(
        TurmaAluno $ta,
        string $turmaDestinoId,
        string $anoLectivoId,
    ): void {

        TurmaAluno::firstOrCreate([
            'turma_id' => $turmaDestinoId,
            'aluno_id' => $ta->aluno_id,
            'ano_lectivo_id' => $anoLectivoId,
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