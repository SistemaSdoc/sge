<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PeriodoProva;
use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Disciplina;
use App\Models\Tenant\Classe;
use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SubmissaoProva;
use App\Notifications\Professor\JustificativaAvaliadaNotificacao;
use App\Notifications\Professor\PrazoProvaNotificacao;
use App\Services\Tenant\PrazoNotificacaoService;
use App\Services\Tenant\ProvaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PrazoProvaController extends Controller
{
    /** Limite máximo de combinações (disciplina × classe × período) por criação */
    private const MAX_COMBINACOES = 200;

    public function __construct(
        private ProvaService $provaService,
        private PrazoNotificacaoService $notificacaoService
    ) {}

    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    /**
     * Opções de período (para multiselect/singular).
     */
    private function periodosOptions(): array
    {
        return collect(PeriodoProva::valores())
            ->map(fn ($v) => ['value' => $v, 'label' => $v])
            ->values()
            ->toArray();
    }

    // ============================================================
    // LISTAGEM E CRIAÇÃO
    // ============================================================

    public function index(Request $request)
    {
        $this->authorize('viewAny', PrazoProva::class);

        $instituicaoId = $this->getInstituicaoId();

        $this->marcarExpirados();

        $prazos = PrazoProva::with(['disciplina', 'classe', 'criador'])
            ->where('instituicao_id', $instituicaoId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->disciplina_id, fn ($q) => $q->where('disciplina_id', $request->disciplina_id))
            ->when($request->ano_letivo, fn ($q) => $q->where('ano_letivo', $request->ano_letivo))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($prazo) => $this->formatarPrazoParaLista($prazo));

        return Inertia::render('tenant/diretor/prazos/index', [
            'prazos'      => $prazos,
            'filters'     => $request->only(['status', 'disciplina_id', 'ano_letivo']),
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome', 'nivel_ensino']),
            'periodos'    => $this->periodosOptions(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', PrazoProva::class);

        return Inertia::render('tenant/diretor/prazos/create', [
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome', 'nivel_ensino']),
            'periodos'    => $this->periodosOptions(),
        ]);
    }

    /**
     * Cria múltiplos prazos (produto cartesiano disciplinas × classes × períodos).
     */
public function store(Request $request)
{
    $this->authorize('create', PrazoProva::class);

    $instituicaoId = $this->getInstituicaoId();

Log::info('store() iniciado', [
    'instituicao_id' => $instituicaoId,
    'user_id'        => auth()->id(),
    'raw_input'      => $request->all(),          // ← tudo o que veio
    'has_disciplina' => $request->has('disciplina_ids'),
    'has_classe'     => $request->has('classe_ids'),
    'has_periodo'    => $request->has('periodo'),
    'has_periodo_ids'=> $request->has('periodo_ids'),
    'periodo_value'  => $request->input('periodo'),
    'periodo_ids_value' => $request->input('periodo_ids'),
]);

    // Normalizar input (disciplinas e classes são arrays; período é string)
    $disciplinaIds = $this->extractIds($request->input('disciplina_ids'));
    $classeIds     = $this->extractIds($request->input('classe_ids'));

    $request->merge([
        'disciplina_ids' => $disciplinaIds,
        'classe_ids'     => $classeIds,
    ]);

    // Validação
    $validated = $request->validate([
        'titulo'           => 'nullable|string|max:255',
        'tipo_prova'       => 'required|in:Prova-Trimestral,Exame-especial,Recurso',

        'disciplina_ids'   => 'required|array|min:1',
        'disciplina_ids.*' => ['required', 'uuid', Rule::exists(Disciplina::class, 'id')],

        'classe_ids'       => 'required|array|min:1',
        'classe_ids.*'     => ['required', 'uuid', 'exists:classes,id'],

        'periodo'          => ['required', 'string', Rule::in(PeriodoProva::valores())],

        'data_inicio'      => 'required|date|before:data_limite',
        'data_limite'      => 'required|date|after:now',
        'observacoes'      => 'nullable|string',
    ], [
        'disciplina_ids.required' => 'Selecione pelo menos uma disciplina.',
        'disciplina_ids.min'      => 'Selecione pelo menos uma disciplina.',
        'classe_ids.required'     => 'Selecione pelo menos uma classe.',
        'classe_ids.min'          => 'Selecione pelo menos uma classe.',
        'periodo.required'        => 'Selecione um período.',
    ]);

    $disciplinas = $validated['disciplina_ids'];
    $classes     = $validated['classe_ids'];
    $periodo     = $validated['periodo'];

    // Limite máximo
    $totalCombinacoes = count($disciplinas) * count($classes);
    if ($totalCombinacoes > self::MAX_COMBINACOES) {
        return back()
            ->with('error', "Demasiadas combinações ({$totalCombinacoes}). Máximo permitido: " . self::MAX_COMBINACOES)
            ->withInput();
    }

    $ano = AnoLectivo::activo();
    if (! $ano) {
        return back()->with('error', 'Nenhum ano letivo ativo.')->withInput();
    }

    try {
        $prazosIds = DB::transaction(function () use ($disciplinas, $classes, $periodo, $validated, $ano, $instituicaoId) {
            $ids = [];

            foreach ($disciplinas as $disciplinaId) {
                foreach ($classes as $classeId) {
                    $prazo = PrazoProva::create([
                        'instituicao_id'  => $instituicaoId,
                        'titulo'          => $validated['titulo'] ?? null,
                        'tipo_prova'      => $validated['tipo_prova'],
                        'disciplina_id'   => $disciplinaId,
                        'classe_id'       => $classeId,
                        'periodo'         => $periodo,  // ← mesmo período para todas
                        'data_inicio'     => $validated['data_inicio'],
                        'data_limite'     => $validated['data_limite'],
                        'observacoes'     => $validated['observacoes'] ?? null,
                        'ano_letivo'      => $ano->nome,
                        'criado_por'      => auth()->id(),
                        'status'          => 'aberto',
                        'permite_reenvio' => false,
                    ]);
                    $ids[] = $prazo->id;
                }
            }

            return $ids;
        });

        // Notificar professores
        foreach ($prazosIds as $prazoId) {
            $prazo = PrazoProva::with(['disciplina', 'classe'])->find($prazoId);
            if ($prazo) {
                $this->notificacaoService->notificarProfessores(
                    $prazo,
                    PrazoProvaNotificacao::TIPO_CRIADO
                );
            }
        }

        Log::info('Prazos criados', [
            'quantidade'     => count($prazosIds),
            'criado_por'     => auth()->id(),
            'instituicao_id' => $instituicaoId,
        ]);

        return redirect()->route('tenant.dashboard.diretor.prazos.index')
            ->with('success', count($prazosIds) . ' prazo(s) criado(s) com sucesso!');
    } catch (\Exception $e) {
        Log::error('Erro ao criar prazos', [
            'error'          => $e->getMessage(),
            'instituicao_id' => $instituicaoId,
            'trace'          => $e->getTraceAsString(),
        ]);

        return back()
            ->with('error', 'Erro ao criar prazos. Nenhum foi criado.')
            ->withInput();
    }
}

    // ============================================================
    // EDIÇÃO E ACTUALIZAÇÃO
    // ============================================================

    public function edit(PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        return Inertia::render('tenant/diretor/prazos/edit', [
            'prazo' => [
                'id'              => $prazo->id,
                'titulo'          => $prazo->titulo,
                'tipo_prova'      => $prazo->tipo_prova,
                'disciplina_id'   => $prazo->disciplina_id,
                'classe_id'       => $prazo->classe_id,
                'data_inicio'     => $prazo->data_inicio->format('Y-m-d\TH:i'),
                'data_limite'     => $prazo->data_limite->format('Y-m-d\TH:i'),
                'periodo'         => $prazo->periodo,
                'observacoes'     => $prazo->observacoes,
                'permite_reenvio' => (bool) $prazo->permite_reenvio,
            ],
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome', 'nivel_ensino']),
            'periodos'    => $this->periodosOptions(),
        ]);
    }

    public function update(Request $request, PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        $validated = $request->validate([
            'disciplina_id'   => ['required', 'uuid', Rule::exists(Disciplina::class, 'id')],
            'classe_id'       => 'required|uuid|exists:classes,id',
            'tipo_prova'      => 'required|in:Prova-Trimestral,Exame-especial,Recurso',
            'titulo'          => 'nullable|string|max:255',
            'observacoes'     => 'nullable|string',
            'data_inicio'     => 'required|date|before:data_limite',
            'data_limite'     => 'required|date|after:now',
            'periodo'         => ['required', 'string', Rule::in(PeriodoProva::valores())],
            'permite_reenvio' => 'boolean',
        ]);

        $prazo->update($validated);

        return redirect()->route('tenant.dashboard.diretor.prazos.show', $prazo)
            ->with('success', 'Prazo atualizado com sucesso.');
    }

    // ============================================================
    // DETALHES E STATUS
    // ============================================================

    public function show(PrazoProva $prazo)
    {
        $this->authorize('view', $prazo);

        $this->marcarExpirados();

        $submissoes = $this->provaService->getSubmissoesParaIndex($prazo, auth()->user());

        $justificativas = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->with(['professor.user', 'avaliador', 'turma'])
            ->get()
            ->map(fn ($just) => [
                'id'             => $just->id,
                'professor'      => $just->professor?->user?->nome ?? 'N/A',
                'turma_nome'     => $just->turma?->nome ?? '—',
                'motivo'         => $just->motivo,
                'data'           => $just->data_justificativa?->format('d/m/Y H:i'),
                'status'         => $just->status,
                'status_label'   => $just->status_label,
                'avaliador'      => $just->avaliador?->nome,
                'data_avaliacao' => $just->data_avaliacao?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('tenant/diretor/prazos/show', [
            'prazo'          => $this->formatarPrazoParaDetalhe($prazo),
            'submissoes'     => $submissoes,
            'justificativas' => $justificativas,
        ]);
    }

    public function status(PrazoProva $prazo)
    {
        $this->authorize('view', $prazo);

        $professores = $this->buscarProfessores($prazo);

        $submissoes = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('estado', '!=', 'substituido')
            ->get()
            ->groupBy(fn ($s) => $s->professor_id . '|' . $s->turma_id)
            ->map(fn ($group) => $group->sortByDesc('versao')->first());

        $justificativas = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->with('avaliador')
            ->get()
            ->groupBy(fn ($j) => $j->professor_id . '|' . $j->turma_id)
            ->map(fn ($group) => $group->first());

        $status = collect();

        foreach ($professores as $professor) {
            $turmas = $this->getTurmasDoProfessorParaPrazo($prazo, $professor);

            if ($turmas->isEmpty()) {
                $status->push([
                    'professor_id'   => $professor->id,
                    'professor_nome' => $professor->user?->nome ?? 'Sem nome',
                    'turma_id'       => null,
                    'turma_nome'     => '—',
                    'submeteu'       => false,
                    'versao'         => null,
                    'estado'         => null,
                    'estado_label'   => null,
                    'badge_class'    => null,
                    'data_submissao' => null,
                    'submissao_id'   => null,
                    'pode_avaliar'   => false,
                    'justificativa'  => null,
                ]);
                continue;
            }

            foreach ($turmas as $turma) {
                $key = $professor->id . '|' . $turma->id;
                $submissao = $submissoes->get($key);
                $justificativa = $justificativas->get($key);

                $status->push([
                    'professor_id'   => $professor->id,
                    'professor_nome' => $professor->user?->nome ?? 'Sem nome',
                    'turma_id'       => $turma->id,
                    'turma_nome'     => $turma->nome,
                    'submeteu'       => ! is_null($submissao),
                    'versao'         => $submissao?->versao,
                    'estado'         => $submissao?->estado,
                    'estado_label'   => $submissao?->estado_label,
                    'badge_class'    => $submissao?->estado_badge_class,
                    'data_submissao' => $submissao?->data_submissao?->format('d/m/Y H:i'),
                    'submissao_id'   => $submissao?->id,
                    'pode_avaliar'   => $submissao && auth()->user()->can('avaliar', $submissao),
                    'justificativa'  => $justificativa ? [
                        'id'                 => $justificativa->id,
                        'motivo'             => $justificativa->motivo,
                        'status'             => $justificativa->status,
                        'status_label'       => $justificativa->status_label,
                        'parecer_diretor'    => $justificativa->parecer_diretor,
                        'data_justificativa' => $justificativa->data_justificativa?->format('d/m/Y H:i'),
                        'data_avaliacao'     => $justificativa->data_avaliacao?->format('d/m/Y H:i'),
                        'avaliador'          => $justificativa->avaliador?->nome,
                    ] : null,
                ]);
            }
        }

        $status = $status
            ->sortBy([
                fn ($a, $b) => $a['submeteu'] <=> $b['submeteu'],
                fn ($a, $b) => strcmp($a['professor_nome'], $b['professor_nome']),
                fn ($a, $b) => strcmp((string) $a['turma_nome'], (string) $b['turma_nome']),
            ])
            ->values();

        return Inertia::render('tenant/diretor/prazos/status', [
            'prazo' => [
                'id'           => $prazo->id,
                'titulo'       => $prazo->titulo ?? $prazo->tipo_prova,
                'disciplina'   => $prazo->disciplina?->only(['id', 'nome', 'sigla']),
                'classe'       => $prazo->classe?->only(['id', 'nome']),
                'data_limite'  => $prazo->data_limite->format('d/m/Y H:i'),
                'status'       => $prazo->status,
                'status_label' => $prazo->status_label,
                'badge_class'  => $prazo->status_badge_class,
            ],
            'professores' => $status,
        ]);
    }

    private function getTurmasDoProfessorParaPrazo(PrazoProva $prazo, Professor $professor): \Illuminate\Support\Collection
    {
        $query = DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('turma_disciplina_professor.professor_id', $professor->id);

        if ($prazo->disciplina_id) {
            $query->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
        }

        if ($prazo->classe_id) {
            $query->where('curso_classe.classe_id', $prazo->classe_id);
        }

        return $query
            ->select('turmas.id', 'turmas.nome')
            ->distinct()
            ->orderBy('turmas.nome')
            ->get()
            ->map(fn ($t) => (object) ['id' => $t->id, 'nome' => $t->nome]);
    }

    // ============================================================
    // ACÇÕES
    // ============================================================

    public function prorrogar(Request $request, PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        $request->validate([
            'nova_data_limite' => 'required|date|after:' . $prazo->data_limite,
        ]);

        try {
            $novaData = Carbon::parse($request->input('nova_data_limite'));
            $this->provaService->prorrogarPrazo($prazo, $novaData);

            $prazo->refresh()->load(['disciplina', 'classe']);
            $this->notificacaoService->notificarProfessores(
                $prazo,
                PrazoProvaNotificacao::TIPO_PRORROGADO
            );

            return back()->with('success', 'Prazo prorrogado com sucesso.');
        } catch (\Exception $e) {
            Log::error('Falha ao prorrogar prazo', [
                'prazo_id' => $prazo->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Erro ao prorrogar: ' . $e->getMessage());
        }
    }

    public function fechar(PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        $this->provaService->fecharPrazo($prazo);

        $prazo->refresh()->load(['disciplina', 'classe']);
        $this->notificacaoService->notificarProfessores(
            $prazo,
            PrazoProvaNotificacao::TIPO_FECHADO
        );

        return back()->with('success', 'Prazo encerrado manualmente.');
    }

    public function avaliar(Request $request, JustificativaNaoSubmissao $justificativa)
    {
        $justificativa->load('prazo');
        $this->authorize('update', $justificativa->prazo);

        $request->validate([
            'status' => 'required|in:aceita,recusada',
            'motivo' => 'nullable|string|max:500',
        ]);

        $justificativa->update([
            'status'          => $request->status,
            'avaliado_por'    => auth()->id(),
            'data_avaliacao'  => now(),
            'parecer_diretor' => $request->motivo,
        ]);

        $professor = $justificativa->professor?->user;
        if ($professor) {
            $professor->notify(new JustificativaAvaliadaNotificacao($justificativa));
        }

        Log::info('Justificativa avaliada', [
            'justificativa_id' => $justificativa->id,
            'status'           => $request->status,
            'avaliado_por'     => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', "Justificativa {$request->status} com sucesso!");
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function extractIds($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        if (isset($input[0]) && is_array($input[0]) && array_key_exists('value', $input[0])) {
            return array_column($input, 'value');
        }

        return array_values(array_filter($input, fn ($v) => ! empty($v)));
    }

    private function marcarExpirados(): void
    {
        PrazoProva::where('instituicao_id', $this->getInstituicaoId())
            ->where('status', 'aberto')
            ->where('data_limite', '<', now())
            ->update(['status' => 'expirado']);
    }

    private function formatarPrazoParaLista(PrazoProva $prazo): array
    {
        return [
            'id'               => $prazo->id,
            'titulo'           => $prazo->titulo ?? $prazo->tipo_prova,
            'tipo_prova'       => $prazo->tipo_prova,
            'disciplina'       => $prazo->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'           => $prazo->classe?->only(['id', 'nome']),
            'data_limite'      => $prazo->data_limite->format('d/m/Y H:i'),
            'data_inicio'      => $prazo->data_inicio->format('d/m/Y H:i'),
            'status'           => $prazo->status,
            'status_label'     => $prazo->status_label,
            'badge_class'      => $prazo->status_badge_class,
            'total_submissoes' => $prazo->submissoes()->count(),
            'ano_letivo'       => $prazo->ano_letivo,
            'periodo'          => $prazo->periodo,
            'criado_por'       => $prazo->criador?->nome ?? 'N/A',
            'can' => [
                'update' => auth()->user()->can('update', $prazo),
                'delete' => auth()->user()->can('delete', $prazo),
            ],
        ];
    }

    private function formatarPrazoParaDetalhe(PrazoProva $prazo): array
    {
        return [
            'id'              => $prazo->id,
            'titulo'          => $prazo->titulo ?? $prazo->tipo_prova,
            'tipo_prova'      => $prazo->tipo_prova,
            'disciplina'      => $prazo->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'          => $prazo->classe?->only(['id', 'nome']),
            'data_inicio'     => $prazo->data_inicio->format('Y-m-d H:i'),
            'data_limite'     => $prazo->data_limite->format('Y-m-d H:i'),
            'status'          => $prazo->status,
            'status_label'    => $prazo->status_label,
            'badge_class'     => $prazo->status_badge_class,
            'observacoes'     => $prazo->observacoes,
            'permite_reenvio' => $prazo->permite_reenvio,
            'ano_letivo'      => $prazo->ano_letivo,
            'periodo'         => $prazo->periodo,
            'can' => [
                'update' => auth()->user()->can('update', $prazo),
                'delete' => auth()->user()->can('delete', $prazo),
            ],
        ];
    }

    private function buscarProfessores(PrazoProva $prazo)
    {
        $instituicaoId = $prazo->instituicao_id;

        $base = Professor::with('user')
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicaoId));

        if ($prazo->disciplina_id) {
            return $base
                ->whereExists(function ($query) use ($prazo) {
                    $query->select(DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                        ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
                        ->when($prazo->classe_id, function ($query) use ($prazo): void {
                            $query->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                                ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                                ->where('curso_classe.classe_id', $prazo->classe_id);
                        });
                })
                ->get();
        }

        return $base->get();
    }
}