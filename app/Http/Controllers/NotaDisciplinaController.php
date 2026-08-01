<?php

namespace App\Http\Controllers;

use App\Helpers\ArredondamentoHelper;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Nota;
use App\Models\PautaStatus;
use App\Models\SolicitacaoEdicaoPauta;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NotaDisciplinaController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {}

    /**
     * Lista as notas dos alunos de uma turma numa disciplina
     */
    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina,
        Request $request
    ) {
        $anoLectivoId = $request->input('ano_lectivo_id') ?? $turma->ano_lectivo_id;

        $tdp = $this->resolveTurmaDisciplinaProfessor($turma, $classeTurnoDisciplina, $anoLectivoId);

        if (! $tdp) {
            return back()->with('warning', 'Ainda não existe uma associação de professor para esta disciplina neste ano lectivo.');
        }

        // Gate::authorize('view', $tdp);
        $periodosLancados = $this->notaService->periodosLancados($tdp->id);
        $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
        $podeLancarNotas = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || ! (
                $periodosLancados[1]
                && $periodosLancados[2]
                && $periodosLancados[3]
            );
        $todosDisponiveis = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || (
                $periodosLancados[1]
                && $periodosLancados[2]
                && $periodosLancados[3]
            );

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/index', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'tdp' => $tdp->id,
            'can' => [
                'create' => Auth::user()->can('create', [Nota::class, $tdp]),
                'export' => Auth::user()->can('export', [Nota::class, $tdp]),
                'overrideLockedPeriods' => Auth::user()->hasAnyRole(['Director', 'Subdirector']),
            ],
            'disciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
            ],
            'alunos' => [
                'data' => $turmaAlunos->getCollection()->map(fn ($ta) => [
                    'turma_aluno_id' => $ta->id,
                    'aluno_id' => $ta->aluno->id,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome,
                    'notas' => $ta->notas
                        ->map(fn ($n) => $this->formatarNota($n))
                        ->keyBy('periodo'),
                ])->values(),
                'current_page' => $turmaAlunos->currentPage(),
                'last_page' => $turmaAlunos->lastPage(),
            ],
            'periodos_lancados' => $periodosLancados,
            'periodos_disponiveis' => $periodosDisponiveis,
            'pode_lancar_notas' => $podeLancarNotas,
            'todos_disponiveis' => $todosDisponiveis,
        ]);

    }

    /**
     * Mostra o formulário de lançamento de notas dos alunos de uma turma numa disciplina
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina,
        Request $request
    ) {
        $anoLectivoId = $request->input('ano_lectivo_id') ?? $turma->ano_lectivo_id;

        $tdp = $this->resolveTurmaDisciplinaProfessor($turma, $classeTurnoDisciplina, $anoLectivoId);

        if (! $tdp) {
            Log::warning('TurmaDisciplinaProfessor não encontrado para notas', [
                'turma_id' => $turma->id,
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                'ano_lectivo_id' => $anoLectivoId,
            ]);

            return back()->with('warning', 'Ainda não existe uma associação de professor para esta disciplina neste ano lectivo.');
        }

        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);
        $periodosLancados = $this->notaService->periodosLancados($tdp->id);
        $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
        $podeLancarNotas = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || ! (
                $periodosLancados[1]
                && $periodosLancados[2]
                && $periodosLancados[3]
            );
        $todosDisponiveis = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || (
                $periodosLancados[1]
                && $periodosLancados[2]
                && $periodosLancados[3]
            );

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/create', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,

            'can' => [
                'create' => Auth::user()->can('create', [Nota::class, $tdp]),
                'overrideLockedPeriods' => Auth::user()->hasAnyRole(['Director', 'Subdirector']),
                // NOVOS:
                'finalizar' => Auth::user()->can('pautas.finalizar'),
                'solicitarEdicao' => Auth::user()->can('pautas.solicitarEdicao'),
            ],
            'data' => [
                'tdp_id' => $tdp->id,
                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                    'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                ],
                'alunos' => [
                    'data' => $turmaAlunos->getCollection()->map(fn ($ta) => [
                        'turma_aluno_id' => $ta->id,
                        'aluno_id' => $ta->aluno->id,
                        'nome' => $ta->aluno->inscricao?->candidato?->nome,
                        'notas' => $ta->notas
                            ->map(fn ($n) => $this->formatarNota($n))
                            ->keyBy('periodo'),
                    ]),
                    'current_page' => $turmaAlunos->currentPage(),
                    'last_page' => $turmaAlunos->lastPage(),
                ],
                'periodos_lancados' => $periodosLancados,
                'periodos_disponiveis' => $periodosDisponiveis,
                'pode_lancar_notas' => $podeLancarNotas,
                'todos_disponiveis' => $todosDisponiveis,
                // NOVOS:
                'pauta_status' => [
                    1 => $this->notaService->getPautaStatus($tdp->id, 1),
                    2 => $this->notaService->getPautaStatus($tdp->id, 2),
                    3 => $this->notaService->getPautaStatus($tdp->id, 3),
                ],
                'dentro_do_prazo' => [
                    1 => $this->notaService->dentroDoPrazo($instituicao->id, 1),
                    2 => $this->notaService->dentroDoPrazo($instituicao->id, 2),
                    3 => $this->notaService->dentroDoPrazo($instituicao->id, 3),
                ],
            ],
        ]);

    }

    /**
     * Salva as notas dos alunos de uma turma numa disciplina
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        string $classeTurnoDisciplinaId
    ) {
        $validated = $request->validate([
            'tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'periodo' => 'required|integer|in:1,2,3,4',
            'notas' => 'required|array',
            'notas.*.mac' => 'nullable|numeric|min:0|max:20',
            'notas.*.npp' => 'nullable|numeric|min:0|max:20',
            'notas.*.npt' => 'nullable|numeric|min:0|max:20',
            'notas.*.faltas' => 'nullable|integer|min:0',
            'notas.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        $tdp = TurmaDisciplinaProfessor::findOrFail($validated['tdp_id']);
        $periodo = (int) $validated['periodo'];
        $isDirector = Auth::user()->hasAnyRole(['Director', 'Subdirector']);

        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);

        if (! $this->notaService->periodoPodeSerLancado($tdp->id, $periodo)) {
            throw ValidationException::withMessages([
                'periodo' => 'Primeiro lança o trimestre anterior para continuar.',
            ]);
        }

        // USAR $instituicao->id directamente — já vem na rota, não precisas navegar relações
        $verificacao = $this->notaService->podeSalvarOuFinalizar(
            $tdp->id,
            $periodo,
            $instituicao->id, // ← directo, sem navegar relações
            $isDirector
        );

        if (! $verificacao['pode']) {
            $mensagem = match ($verificacao['motivo']) {
                'pauta_finalizada' => 'Esta pauta já foi finalizada. Solicite autorização ao director para editar.',
                'prazo_encerrado' => 'O prazo de lançamento terminou. Solicite autorização ao director.',
                default => 'Não é possível salvar as notas.',
            };
            throw ValidationException::withMessages(['periodo' => $mensagem]);
        }

        $this->notaService->lancarNotas($validated['notas'], $validated['tdp_id'], $periodo);

        TurmaAluno::with([
            'aluno',
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])->whereIn('id', array_keys($validated['notas']))->each(function (TurmaAluno $ta): void {
            $this->pautaService->actualizarResultadoAluno($ta);
        });

        $accao = $request->input('accao', 'guardar'); // 'guardar' ou 'finalizar'

        if ($accao === 'finalizar') {
            PautaStatus::updateOrCreate(
                ['turma_disciplina_professor_id' => $tdp->id, 'periodo' => $periodo],
                ['status' => 'finalizada', 'finalizada_em' => now(), 'finalizada_automaticamente' => false]
            );

            return back()->with('success', 'Pauta finalizada com sucesso.');
        }

        return back()->with('success', 'Rascunho guardado.');

    }

    // NOVO endpoint
    // public function finalizar(Request $request, ...$routeParams)
    // {
    //     $validated = $request->validate([
    //         'tdp_id' => 'required|exists:turma_disciplina_professor,id',
    //         'periodo' => 'required|integer|in:1,2,3,4',
    //         'notas' => 'required|array',
    //         'notas.*.mac' => 'nullable|numeric|min:0|max:20',
    //         'notas.*.npp' => 'nullable|numeric|min:0|max:20',
    //         'notas.*.npt' => 'nullable|numeric|min:0|max:20',
    //         'notas.*.faltas' => 'nullable|integer|min:0',
    //         'notas.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
    //     ]);

    //     $tdp = TurmaDisciplinaProfessor::findOrFail($validated['tdp_id']);
    //     $periodo = (int) $validated['periodo'];
    //     $isDirector = Auth::user()->hasAnyRole(['Director', 'Subdirector']);

    //     $verificacao = $this->notaService->podeSalvarOuFinalizar(
    //         $tdp->id,
    //         $periodo,
    //         $tdp = TurmaDisciplinaProfessor::with([
    //             'turma.cursoClasseTurno.cursoClasse.cursoTutelado'
    //         ])->findOrFail($validated['tdp_id']),
    //         $isDirector
    //     );

    //     if (!$verificacao['pode']) {
    //         throw ValidationException::withMessages(['periodo' => '...']);
    //     }

    //     // Salva as notas
    //     $this->notaService->lancarNotas($validated['notas'], $tdp->id, $periodo);

    //     // Finaliza
    //     PautaStatus::updateOrCreate(
    //         ['turma_disciplina_professor_id' => $tdp->id, 'periodo' => $periodo],
    //         [
    //             'status' => 'finalizada',
    //             'finalizada_em' => now(),
    //             'finalizada_automaticamente' => false,
    //         ]
    //     );

    //     // Marcar autorização como usada (se era edição autorizada)
    //     SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
    //         ->where('periodo', $periodo)
    //         ->where('status', 'aprovada')
    //         ->whereNull('usada_em')
    //         ->update(['usada_em' => now()]);

    //     // recalculo...

    //     return back()->with('success', 'Pauta finalizada com sucesso.');
    // }

    private function resolveTurmaDisciplinaProfessor(
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina,
        ?string $anoLectivoId = null,
    ): ?TurmaDisciplinaProfessor {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->when($anoLectivoId, function ($query) use ($anoLectivoId) {
                $query->whereHas('classeTurnoDisciplina', function ($subQuery) use ($anoLectivoId) {
                    $subQuery->where('ano_lectivo_id', $anoLectivoId);
                });
            })
            ->first();

        if ($tdp) {
            return $tdp;
        }

        if ($anoLectivoId) {
            $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
                ->where('turma_id', $turma->id)
                ->whereHas('classeTurnoDisciplina', function ($query) use ($classeTurnoDisciplina, $anoLectivoId) {
                    $query->where('curso_classe_turno_id', $classeTurnoDisciplina->curso_classe_turno_id)
                        ->where('disciplina_id', $classeTurnoDisciplina->disciplina_id)
                        ->where('ano_lectivo_id', $anoLectivoId);
                })
                ->first();

            if ($tdp) {
                return $tdp;
            }
        }

        return TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->whereHas('classeTurnoDisciplina', function ($query) use ($classeTurnoDisciplina) {
                $query->where('curso_classe_turno_id', $classeTurnoDisciplina->curso_classe_turno_id)
                    ->where('disciplina_id', $classeTurnoDisciplina->disciplina_id);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Função para formatar as notas de uma aluno de uma disciplina
     */
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
