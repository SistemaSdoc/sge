<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ArredondamentoHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\SolicitacaoEdicaoPauta;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NotaDisciplinaController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {
    }

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

        if (!$tdp) {
            return back()->with('warning', 'Ainda não existe uma associação de professor para esta disciplina neste ano lectivo.');
        }

        // Gate::authorize('view', $tdp);
        $periodosLancados = $this->notaService->periodosLancados($tdp->id);
        $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
        $podeLancarNotas = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || !(
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

        // Adicionar antes do map dos alunos
        $pautaStatusIndex = [
            1 => $this->notaService->getPautaStatusSoLeitura($tdp->id, 1),
            2 => $this->notaService->getPautaStatusSoLeitura($tdp->id, 2),
            3 => $this->notaService->getPautaStatusSoLeitura($tdp->id, 3),
        ];

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('turma_aluno.situacao', 'activo')
                        ->where('turma_aluno.activo', true);
                })->orWhere('turma_aluno.situacao', 'concluido');
            })
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        $professorDono = Auth::user()->professor?->id === $tdp->professor_id;
        $podeVerRascunho = $professorDono || Auth::user()->hasAnyRole(['Director', 'Subdirector']);

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
                'data' => $turmaAlunos->getCollection()->map(fn($ta) => [
                    'turma_aluno_id' => $ta->id,
                    'aluno_id' => $ta->aluno->id,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome,
                    'notas' => $ta->notas
                        ->filter(function ($nota) use ($tdp, $podeVerRascunho) {
                            if ($nota->turma_disciplina_professor_id !== $tdp->id) {
                                return false;
                            }
                            $status = $this->notaService->getPautaStatusSoLeitura($tdp->id, $nota->periodo);
                            $eRascunho = $status === null || $status->status === 'rascunho';
                            if ($eRascunho && !$podeVerRascunho) {
                                return false;
                            }

                            return true;
                        })
                        ->map(function ($n) use ($pautaStatusIndex) {
                            $status = $pautaStatusIndex[$n->periodo] ?? null;

                            return [
                                ...$this->formatarNota($n),
                                'is_rascunho' => $status === null || $status->status === 'rascunho',
                            ];
                        })
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
        $user = Auth::user();
        $anoLectivoId = $request->input('ano_lectivo_id') ?? $turma->ano_lectivo_id;

        $tdp = $this->resolveTurmaDisciplinaProfessor($turma, $classeTurnoDisciplina, $anoLectivoId);

        if (!$tdp) {
            return back()->with('warning', 'Ainda não existe uma associação de professor para esta disciplina neste ano lectivo.');
            Log::info('Chamando resolveTurmaDisciplinaProfessor', [
                'turma_id' => $turma->id,
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                'ano_lectivo_id' => $anoLectivoId,
            ]);

            $tdp = $this->resolveTurmaDisciplinaProfessor($turma, $classeTurnoDisciplina, $anoLectivoId);
            Log::info('resolveTurmaDisciplinaProfessor retornou', ['tdp_id' => $tdp?->id, 'tdp_null' => is_null($tdp)]);

            if (!$tdp) {
                Log::warning('TurmaDisciplinaProfessor não encontrado para notas', [
                    'turma_id' => $turma->id,
                    'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                    'ano_lectivo_id' => $anoLectivoId,
                ]);

                return back()->with('warning', 'Ainda não existe uma associação de professor para esta disciplina neste ano lectivo.');
            }

            $professorDono = Auth::user()->professor?->id === $tdp->professor_id;
            $podeVerRascunho = $professorDono || Auth::user()->hasAnyRole(['Director', 'Subdirector']);

            Log::info('Gate::authorize view');
            Gate::authorize('view', $tdp);
            Log::info('Gate::authorize create');
            Gate::authorize('create', [Nota::class, $tdp]);

            Log::info('Buscando periodosLancados');
            $periodosLancados = $this->notaService->periodosLancados($tdp->id);
            Log::info('periodosLancados', $periodosLancados);

            Log::info('Buscando periodosDisponiveis');
            $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
            Log::info('periodosDisponiveis', $periodosDisponiveis);

            Log::info('Calculando podeLancarNotas');
            $podeLancarNotas = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
                || !(
                    $periodosLancados[1]
                    && $periodosLancados[2]
                    && $periodosLancados[3]
                );
            Log::info('podeLancarNotas', ['value' => $podeLancarNotas]);

            Log::info('Calculando todosDisponiveis');
            $todosDisponiveis = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
                || (
                    $periodosLancados[1]
                    && $periodosLancados[2]
                    && $periodosLancados[3]
                );
            Log::info('todosDisponiveis', ['value' => $todosDisponiveis]);

            Log::info('Buscando turmaAlunos');
            $turmaAlunos = TurmaAluno::query()
                ->select('turma_aluno.*')
                ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
                ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
                ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
                ->with([
                    'aluno.inscricao.candidato:id,nome',
                    'notas' => fn($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
                ])
                ->where('turma_aluno.turma_id', $turma->id)
                ->where(function ($q) {
                    $q->where(function ($q) {
                        $q->where('turma_aluno.situacao', 'activo')
                            ->where('turma_aluno.activo', true);
                    })->orWhere('turma_aluno.situacao', 'concluido');
                })
                ->orderBy('candidatos.nome')
                ->paginate(20, ['*'], 'page_alunos');
            Log::info('turmaAlunos buscados', ['total' => $turmaAlunos->total()]);

            Log::info('Mapeando alunos para o frontend');
            $alunosMapeados = $turmaAlunos->getCollection()->map(fn($ta) => [
                'turma_aluno_id' => $ta->id,
                'aluno_id' => $ta->aluno->id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'notas' => $ta->notas
                    ->filter(fn($nota) => $nota->turma_disciplina_professor_id === $tdp->id)
                    ->map(fn($n) => [
                        ...$this->formatarNota($n),
                        'is_rascunho' => ($pautaStatus[$n->periodo]->status ?? 'rascunho') === 'rascunho',
                    ])
                    ->keyBy('periodo'),
            ]);

            Log::info('Alunos mapeados com sucesso', ['count' => $alunosMapeados->count()]);

            Log::info('Verificando permissions can');
            $can = [
                'create' => Auth::user()->can('create', [Nota::class, $tdp]),
                'overrideLockedPeriods' => Auth::user()->hasAnyRole(['Director', 'Subdirector']),
                'finalizar' => Auth::user()->can('pautas.finalizar'),
                'solicitarEdicao' => Auth::user()->can('pautas.solicitarEdicao'),
            ];
            Log::info('Permissions verificadas', $can);

            Log::info('Buscando getPautaStatus');
            $pautaStatus = [
                1 => $this->notaService->getPautaStatus($tdp->id, 1),
                2 => $this->notaService->getPautaStatus($tdp->id, 2),
                3 => $this->notaService->getPautaStatus($tdp->id, 3),
            ];
            Log::info('pautaStatus', $pautaStatus);

            Log::info('Buscando dentroDoPrazo');
            try {
                $dentroDoPrazo = [
                    1 => $this->notaService->dentroDoPrazo($instituicao->id, 1),
                    2 => $this->notaService->dentroDoPrazo($instituicao->id, 2),
                    3 => $this->notaService->dentroDoPrazo($instituicao->id, 3),
                ];
                Log::info('dentroDoPrazo', $dentroDoPrazo);
            } catch (\Exception $e) {
                Log::error('Erro em dentroDoPrazo', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            Log::info('Renderizando Inertia');
            try {
                return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/create', [
                    'instituicao' => $instituicao->id,
                    'cursoTutelado' => $cursoTutelado->id,
                    'cursoClasse' => $cursoClasse->id,
                    'cursoClasseTurno' => $cursoClasseTurno->id,
                    'turma' => $turma->id,
                    'classeTurnoDisciplina' => $classeTurnoDisciplina->id,

                    'can' => $can,
                    'data' => [
                        'tdp_id' => $tdp->id,
                        'disciplina' => [
                            'id' => $classeTurnoDisciplina->id,
                            'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                            'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                        ],
                        'alunos' => [
                            'data' => $alunosMapeados,
                            'current_page' => $turmaAlunos->currentPage(),
                            'last_page' => $turmaAlunos->lastPage(),
                        ],
                        'periodos_lancados' => $periodosLancados,
                        'periodos_disponiveis' => $periodosDisponiveis,
                        'pode_lancar_notas' => $podeLancarNotas,
                        'todos_disponiveis' => $todosDisponiveis,
                        'pauta_status' => $pautaStatus,
                        'dentro_do_prazo' => $dentroDoPrazo,
                        'autorizacao_ate' => [   // ← adicionar isto
                            1 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 1)->where('status', 'aprovada')
                                ->whereNull('usada_em')->where('prazo_edicao_ate', '>', now())
                                ->first()?->prazo_edicao_ate?->toISOString(),
                            2 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 2)->where('status', 'aprovada')
                                ->whereNull('usada_em')->where('prazo_edicao_ate', '>', now())
                                ->first()?->prazo_edicao_ate?->toISOString(),
                            3 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 3)->where('status', 'aprovada')
                                ->whereNull('usada_em')->where('prazo_edicao_ate', '>', now())
                                ->first()?->prazo_edicao_ate?->toISOString(),
                        ],
                        'tem_solicitacao_pendente' => [
                            1 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 1)->where('status', 'pendente')->exists(),
                            2 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 2)->where('status', 'pendente')->exists(),
                            3 => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                                ->where('periodo', 3)->where('status', 'pendente')->exists(),
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Erro ao renderizar Inertia', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;

            }
        }


        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);

        $isDirecao = $user->hasAnyRole(['Director', 'Subdirector']);

        $periodosLancados = $this->notaService->periodosLancados($tdp->id);
        $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
        $todosPeriodosLancados = $periodosLancados[1] && $periodosLancados[2] && $periodosLancados[3];

        $podeLancarNotas = $isDirecao || !$todosPeriodosLancados;
        $todosDisponiveis = $isDirecao || $todosPeriodosLancados;

        $pautaStatus = collect([1, 2, 3])
            ->mapWithKeys(fn($periodo) => [$periodo => $this->notaService->getPautaStatus($tdp->id, $periodo)]);

        $dentroDoPrazo = collect([1, 2, 3])
            ->mapWithKeys(fn($periodo) => [$periodo => $this->notaService->dentroDoPrazo($instituicao->id, $periodo)]);

        $autorizacaoAte = collect([1, 2, 3])->mapWithKeys(function ($periodo) use ($tdp) {
            $prazo = SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                ->where('periodo', $periodo)
                ->where('status', 'aprovada')
                ->whereNull('usada_em')
                ->where('prazo_edicao_ate', '>', now())
                ->value('prazo_edicao_ate');

            return [$periodo => $prazo?->toISOString()];
        });

        $temSolicitacaoPendente = collect([1, 2, 3])->mapWithKeys(fn($periodo) => [
            $periodo => SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                ->where('periodo', $periodo)
                ->where('status', 'pendente')
                ->exists(),
        ]);

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('turma_aluno.situacao', 'activo')
                        ->where('turma_aluno.activo', true);
                })->orWhere('turma_aluno.situacao', 'concluido');
            })
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        $alunosMapeados = $turmaAlunos->getCollection()->map(fn($ta) => [
            'turma_aluno_id' => $ta->id,
            'aluno_id' => $ta->aluno->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'notas' => $ta->notas
                ->map(fn($n) => [
                    ...$this->formatarNota($n),
                    'is_rascunho' => ($pautaStatus[$n->periodo]->status ?? 'rascunho') === 'rascunho',
                ])
                ->keyBy('periodo'),
        ]);

        $permissions = [
            'curso' => [
                'view' => $user->can('view', $cursoTutelado),
            ],
            'classe' => [
                'view' => $user->can('view', $cursoClasse),
            ],
            'turno' => [
                'view' => $user->can('view', $cursoClasseTurno),
            ],
            'turma' => [
                'view' => $user->can('view', $turma),
            ],
            'notas' => [
                'create' => $user->can('create', [Nota::class, $tdp]),
                'overrideLockedPeriods' => $isDirecao,
                'finalizar' => $user->can('pautas.finalizar'),
                'solicitarEdicao' => $user->can('pautas.solicitarEdicao'),
            ],
        ];

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/create', [
            'instituicao' => [
                'id' => $instituicao->id,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome,
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome,
            ],
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
            ],
            'classeTurnoDisciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'nome' => $classeTurnoDisciplina->disciplina->nome,
            ],
            'can' => $permissions,
            'data' => [
                'tdp_id' => $tdp->id,
                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                    'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                ],
                'alunos' => [
                    'data' => $alunosMapeados,
                    'current_page' => $turmaAlunos->currentPage(),
                    'last_page' => $turmaAlunos->lastPage(),
                ],
                'periodos_lancados' => $periodosLancados,
                'periodos_disponiveis' => $periodosDisponiveis,
                'pode_lancar_notas' => $podeLancarNotas,
                'todos_disponiveis' => $todosDisponiveis,
                'pauta_status' => $pautaStatus,
                'dentro_do_prazo' => $dentroDoPrazo,
                'autorizacao_ate' => $autorizacaoAte,
                'tem_solicitacao_pendente' => $temSolicitacaoPendente,
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

        if (!$this->notaService->periodoPodeSerLancado($tdp->id, $periodo)) {
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

        if (!$verificacao['pode']) {
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

            // Marcar autorização usada — tanto edicao como lancamento
            SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $tdp->id)
                ->where('periodo', $periodo)
                ->where('status', 'aprovada')
                ->whereNull('usada_em')
                ->update(['usada_em' => now()]);

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
        $yearCandidates = array_values(array_unique(array_filter([
            $anoLectivoId,
            $turma->ano_lectivo_id,
            $classeTurnoDisciplina->ano_lectivo_id,
        ], fn($value) => filled($value))));

        $baseQuery = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id);

        $tdp = $baseQuery->first();

        if ($tdp) {
            return $tdp;
        }

        foreach ($yearCandidates as $candidateYearId) {
            $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
                ->where('turma_id', $turma->id)
                ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
                ->whereHas('classeTurnoDisciplina', function ($query) use ($candidateYearId): void {
                    $query->where('ano_lectivo_id', $candidateYearId);
                })
                ->first();

            if ($tdp) {
                return $tdp;
            }
        }

        return TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->whereHas('classeTurnoDisciplina', function ($query) use ($classeTurnoDisciplina): void {
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
