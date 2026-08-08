<?php

namespace App\Http\Controllers;

use App\Helpers\ArredondamentoHelper;
use App\Models\Aluno;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasseTurno;
use App\Models\Nota;
use App\Models\PautaStatus;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use App\Services\PreencherHistoricoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PreencherHistoricoController extends Controller
{
    public function __construct(
        private readonly PreencherHistoricoService $service,
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {
    }

    /**
     * Mostra o formulário de lançamento do histórico académico de um aluno.
     *
     * A turma histórica já foi criada pelo método confirmar().
     * Aqui listamos todas as disciplinas dessa turma e as notas já lançadas.
     */
    public function create(Request $request, Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $turmaAluno = TurmaAluno::with([
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
        ])
            ->where('aluno_id', $aluno->id)
            ->findOrFail($request->query('turma_aluno_id'));

        $turma = $turmaAluno->turma;
        $cursoClasseTurno = $turma->cursoClasseTurno;

        // Todos os TDPs desta turma (uma linha por disciplina)
        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        // Notas já lançadas para este turmaAluno, indexadas por tdp_id
        $notasExistentes = Nota::where('turma_aluno_id', $turmaAluno->id)
            ->get()
            ->groupBy('turma_disciplina_professor_id');

        $disciplinas = $tdps->map(function (TurmaDisciplinaProfessor $tdp) use ($notasExistentes) {
            $notas = $notasExistentes->get($tdp->id, collect());

            return [
                'tdp_id' => $tdp->id,
                'id' => $tdp->classeTurnoDisciplina->id,
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                'notas' => $notas->keyBy('periodo')->map(fn($n) => $this->formatarNota($n)),
            ];
        });

        $instituicao = $turma->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado
            ->instituicao;

        return Inertia::render('preencher-historico/create', [
            'aluno' => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'matricula' => $aluno->matricula,
            ],
            'turmaAluno' => [
                'id' => $turmaAluno->id,
            ],
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'ano_lectivo' => $turma->anoLectivo?->nome,
                'classe' => $cursoClasseTurno->cursoClasse->classe->nome ?? null,
                'turno' => $cursoClasseTurno->turno?->nome ?? null,
            ],
            'disciplinas' => $disciplinas->values(),
            'can' => [
                'lancar' => Auth::user()->can('update', $aluno),
            ],
        ]);
    }

    /**
     * Salva as notas do histórico académico.
     *
     * Recebe: turma_aluno_id, periodo, notas[tdp_id][mac|npp|npt|faltas]
     * Segue o mesmo padrão do NotaDisciplinaController@store.
     */

    public function store(Request $request, Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $validated = $request->validate([
            'turma_aluno_id' => 'required|uuid|exists:turma_aluno,id',
            'periodo' => 'required|integer|in:1,2,3',
            'notas' => 'required|array',
            'notas.*.mac' => 'nullable|numeric|min:0|max:20',
            'notas.*.npp' => 'nullable|numeric|min:0|max:20',
            'notas.*.npt' => 'nullable|numeric|min:0|max:20',
            'notas.*.faltas' => 'nullable|integer|min:0',
            'accao' => 'required|in:guardar,finalizar',
        ]);

        $turmaAluno = TurmaAluno::with([
            'turma',
            'aluno',
            'turma.cursoClasseTurno.cursoClasse',
        ])->findOrFail($validated['turma_aluno_id']);

        $periodo = (int) $validated['periodo'];

        foreach ($validated['notas'] as $tdpId => $valores) {
            $tdp = TurmaDisciplinaProfessor::findOrFail($tdpId);
            $mac = is_numeric($valores['mac']) ? (float) $valores['mac'] : null;
            $npp = is_numeric($valores['npp']) ? (float) $valores['npp'] : null;
            $npt = is_numeric($valores['npt']) ? (float) $valores['npt'] : null;
            $faltas = is_numeric($valores['faltas']) ? (int) $valores['faltas'] : null;

            // média simples — confirma se é esta a fórmula do teu sistema
            $media = ($mac !== null && $npp !== null && $npt !== null)
                ? ArredondamentoHelper::roundToHalf(($mac + $npp + $npt) / 3)
                : null;

            $situacaoTrimestral = match (true) {
                $media === null => null,
                $media >= 10 && ($faltas ?? 0) < 20 => 'APTO',
                default => 'N/APTO',
            };

            Nota::updateOrCreate(
                [
                    'turma_aluno_id' => $turmaAluno->id,
                    'turma_disciplina_professor_id' => $tdp->id,
                    'periodo' => $periodo,
                ],
                [
                    'mac' => $mac,
                    'nota_prova_professor' => $npp,
                    'nota_prova_trimestral' => $npt,
                    'faltas' => $faltas,
                    'media_trimestral' => $media,
                    'situacao_trimestral' => $situacaoTrimestral,
                ]
            );
        }

        if ($validated['accao'] === 'finalizar') {
            $tdpIds = TurmaDisciplinaProfessor::where('turma_id', $turmaAluno->turma_id)
                ->pluck('id');

            foreach ($tdpIds as $tdpId) {
                PautaStatus::updateOrCreate(
                    ['turma_disciplina_professor_id' => $tdpId, 'periodo' => $periodo],
                    ['status' => 'finalizada', 'finalizada_em' => now(), 'finalizada_automaticamente' => false]
                );
            }

            return back()->with('success', 'Histórico do trimestre ' . $periodo . ' finalizado com sucesso.');
        }

        return back()->with('success', 'Rascunho do trimestre ' . $periodo . ' guardado.');
    }

    /**
     * POST /historico/:aluno/confirmar
     * Cria o TurmaAluno histórico e redireciona para o create.
     */
    public function confirmar(Request $request, Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $validated = $request->validate([
            'turma_id' => 'required|uuid|exists:turmas,id',
        ]);

        try {
            $instituicaoId = Auth::user()?->instituicao_id;
            $turmaAluno = $this->service->criarTurmaAlunoHistorico(
                $aluno,
                $validated['turma_id'],
                $instituicaoId
            );

            // Inertia precisa de um redirect para navegar —
            // o modal fecha via onSuccess antes da navegação acontecer
            return redirect()
                ->to(
                    route('preencher-historico.create', ['aluno' => $aluno->id])
                    . '?turma_aluno_id=' . $turmaAluno->id
                )
                ->with('success', 'Histórico criado. Procede ao lançamento de notas.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function formatarNota(Nota $n): array
    {
        return [
            'id' => $n->id,
            'periodo' => $n->periodo,
            'mac' => $n->mac,
            'nota_prova_professor' => $n->nota_prova_professor,
            'nota_prova_trimestral' => $n->nota_prova_trimestral,
            'media_trimestral' => ArredondamentoHelper::roundToHalf($n->media_trimestral),
            'media_final' => ArredondamentoHelper::roundToHalf($n->media_final),
            'faltas' => $n->faltas,
            'situacao_trimestral' => $n->situacao_trimestral,
            'situacao_anual' => $n->situacao_anual,
        ];
    }
}