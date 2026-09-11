<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\Disciplina;
use App\Models\PrazoProva;
use App\Models\Professor;
use App\Models\SubmissaoProva;
use App\Models\JustificativaNaoSubmissao;
use App\Notifications\PrazoProvaNotificacao;
use App\Services\PrazoNotificacaoService;
use App\Services\ProvaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PrazoProvaController extends Controller
{
    /** Limite máximo de combinações (disciplina × classe) por criação */
    private const MAX_COMBINACOES = 50;

    public function __construct(
        ProvaService $provaService,
        private PrazoNotificacaoService $notificacaoService
    ) {
        $this->provaService = $provaService;
    }

    /**
     * Retorna o instituicao_id do utilizador autenticado.
     */
    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    // ============================================================
    // LISTAGEM E CRIAÇÃO
    // ============================================================

    /**
     * Lista todos os prazos com filtros e dados paginados.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PrazoProva::class);

        $instituicaoId = $this->getInstituicaoId();

        $this->marcarExpirados();

        $prazos = PrazoProva::with(['disciplina', 'classe', 'criador'])
            ->where('instituicao_id', $instituicaoId)  //   FILTRO apenas no prazo
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->disciplina_id, fn($q) => $q->where('disciplina_id', $request->disciplina_id))
            ->when($request->ano_letivo, fn($q) => $q->where('ano_letivo', $request->ano_letivo))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($prazo) => $this->formatarPrazoParaLista($prazo));

        return Inertia::render('diretor/prazos/index', [
            'prazos'      => $prazos,
            'filters'     => $request->only(['status', 'disciplina_id', 'ano_letivo']),
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),  //   sem filtro
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome']),               //   sem filtro
        ]);
    }

    /**
     * Exibe formulário para criar novo prazo.
     */
    public function create()
    {
        $this->authorize('create', PrazoProva::class);

        return Inertia::render('diretor/prazos/Create', [
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),      //   sem filtro
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome', 'nivel_ensino']),  //   sem filtro
        ]);
    }

    /**
     * Armazena múltiplos prazos (produto cartesiano disciplinas × classes).
     */
    public function store(Request $request)
    {
        $this->authorize('create', PrazoProva::class);

        $instituicaoId = $this->getInstituicaoId();

        Log::info(' store() iniciado', [
            'instituicao_id' => $instituicaoId,
            'user_id'        => auth()->id(),
            'data'           => $request->all(),
        ]);

        // Extrair IDs primeiro
        $disciplinaIds = $this->extractIds($request->input('disciplina_ids'));
        $classeIds     = $this->extractIds($request->input('classe_ids'));

        $request->merge([
            'disciplina_ids' => $disciplinaIds,
            'classe_ids'     => $classeIds,
        ]);

        // Validação
        $validated = $request->validate([
            'titulo'           => 'nullable|string|max:255',
            'tipo_prova'       => 'required|in:teste,exame,ficha,recuperacao',
            'disciplina_ids'   => 'nullable|array',
            'disciplina_ids.*' => 'nullable|uuid|exists:disciplinas,id',
            'classe_ids'       => 'nullable|array',
            'classe_ids.*'     => 'nullable|uuid|exists:classes,id',
            'data_inicio'      => 'required|date|before:data_limite',
            'data_limite'      => 'required|date|after:now',
            'periodo'          => 'required|string|max:20',
            'observacoes'      => 'nullable|string',
        ]);

        $disciplinas = !empty($validated['disciplina_ids']) ? $validated['disciplina_ids'] : [null];
        $classes     = !empty($validated['classe_ids'])     ? $validated['classe_ids']     : [null];

        // Limite máximo
        $totalCombinacoes = count($disciplinas) * count($classes);
        if ($totalCombinacoes > self::MAX_COMBINACOES) {
            return back()
                ->with('error', "Demasiadas combinações ({$totalCombinacoes}). Máximo permitido: " . self::MAX_COMBINACOES)
                ->withInput();
        }

        // Ano letivo ativo
        $ano = AnoLectivo::getAnoAtivo();
        if (!$ano) {
            return back()->with('error', 'Nenhum ano letivo ativo.')->withInput();
        }

        // Transação atómica
        try {
            $prazosIds = DB::transaction(function () use ($disciplinas, $classes, $validated, $ano, $instituicaoId) {
                $ids = [];

                foreach ($disciplinas as $disciplinaId) {
                    foreach ($classes as $classeId) {
                        $prazo = PrazoProva::create([
                            'instituicao_id'  => $instituicaoId,  //  
                            'titulo'          => $validated['titulo'] ?? null,
                            'tipo_prova'      => $validated['tipo_prova'],
                            'disciplina_id'   => $disciplinaId,
                            'classe_id'       => $classeId,
                            'data_inicio'     => $validated['data_inicio'],
                            'data_limite'     => $validated['data_limite'],
                            'periodo'         => $validated['periodo'],
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

            Log::info('✅ Prazos criados', [
                'quantidade'     => count($prazosIds),
                'criado_por'     => auth()->id(),
                'instituicao_id' => $instituicaoId,
            ]);

            $total = count($prazosIds);
            return redirect()->route('prazos.index')
                ->with('success', "{$total} prazo(s) criado(s) com sucesso!");

        } catch (\Exception $e) {
            Log::error('  Erro ao criar prazos', [
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

    /**
     * Exibe formulário de edição do prazo.
     */
    public function edit(PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        return Inertia::render('diretor/prazos/edit', [
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
            'disciplinas' => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),  //  sem filtro
            'classes'     => Classe::orderBy('nome')->get(['id', 'nome']),               //   sem filtro
        ]);
    }

    /**
     * Atualiza um prazo existente.
     */
    public function update(Request $request, PrazoProva $prazo)
    {
        $this->authorize('update', $prazo);

        $validated = $request->validate($this->regrasValidacao());
        $validated['permite_reenvio'] = $request->boolean('permite_reenvio');

        $prazo->update($validated);

        return redirect()->route('prazos.show', $prazo)
            ->with('success', 'Prazo atualizado com sucesso.');
    }

    // ============================================================
    // DETALHES E STATUS
    // ============================================================

    /**
     * Exibe detalhes do prazo, suas submissões e justificativas.
     */
    public function show(PrazoProva $prazo)
    {
        $this->marcarExpirados();

        $submissoes = $this->provaService->getSubmissoesParaIndex($prazo, auth()->user());

        $justificativas = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->with(['professor.user', 'avaliador'])
            ->get()
            ->map(fn($just) => [
                'id'             => $just->id,
                'professor'      => $just->professor?->user?->nome ?? 'N/A',
                'motivo'         => $just->motivo,
                'data'           => $just->data_justificativa->format('d/m/Y H:i'),
                'status'         => $just->status,
                'status_label'   => $just->status_label,
                'avaliador'      => $just->avaliador?->nome ?? null,
                'data_avaliacao' => $just->data_avaliacao?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('diretor/prazos/show', [
            'prazo'          => $this->formatarPrazoParaDetalhe($prazo),
            'submissoes'     => $submissoes,
            'justificativas' => $justificativas,
        ]);
    }

    /**
     * Exibe status de cumprimento (professores que submeteram/não).
     */
    public function status(PrazoProva $prazo)
    {
        $this->authorize('view', $prazo);

        $professores = $this->buscarProfessores($prazo);

        //   Carregar tudo de uma vez (3 queries em vez de 2N)
        $submissoes = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('estado', '!=', 'substituido')
            ->get()
            ->groupBy('professor_id')
            ->map(fn($group) => $group->sortByDesc('versao')->first());

        $justificativas = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->with('avaliador')
            ->get()
            ->keyBy('professor_id');

        $status = $professores->map(function ($professor) use ($submissoes, $justificativas) {
            $submissao = $submissoes->get($professor->id);
            $justificativa = $justificativas->get($professor->id);

            return [
                'professor_id'   => $professor->id,
                'professor_nome' => $professor->user->nome ?? 'Sem nome',
                'submeteu'       => !is_null($submissao),
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
                    'data_justificativa' => $justificativa->data_justificativa->format('d/m/Y H:i'),
                    'data_avaliacao'     => $justificativa->data_avaliacao?->format('d/m/Y H:i'),
                    'avaliador'          => $justificativa->avaliador?->nome ?? null,
                ] : null,
            ];
        });

        $status = $status->sortByDesc('submeteu')->values();

        return Inertia::render('diretor/prazos/status', [
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

    // ============================================================
    // ACÇÕES (PRORROGAR, FECHAR, AVALIAR)
    // ============================================================

    /**
     * Prorroga o prazo com nova data limite.
     */
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

    /**
     * Fecha o prazo manualmente.
     */
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

    /**
     * Avalia uma justificativa (aceitar/recusar).
     */
    public function avaliar(Request $request, JustificativaNaoSubmissao $justificativa)
    {
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
            $professor->notify(new \App\Notifications\JustificativaAvaliadaNotificacao($justificativa));
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
    // MÉTODOS PRIVADOS AUXILIARES
    // ============================================================

    /**
     * Extrai os valores (IDs) de um array de objetos { value, label } ou strings.
     */
    private function extractIds($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        if (isset($input[0]) && is_array($input[0]) && array_key_exists('value', $input[0])) {
            return array_column($input, 'value');
        }

        return array_values(array_filter($input, fn($v) => !empty($v)));
    }

    /**
     * Atualiza prazos abertos cuja data limite já passou.
     *   Filtra pela instituição.
     */
    private function marcarExpirados(): void
    {
        PrazoProva::where('instituicao_id', $this->getInstituicaoId())
            ->where('status', 'aberto')
            ->where('data_limite', '<', now())
            ->update(['status' => 'expirado']);
    }

    /**
     * Regras de validação para update.
     */
    private function regrasValidacao(): array
    {
        return [
            'disciplina_id' => 'nullable|uuid|exists:disciplinas,id',
            'classe_id'     => 'nullable|uuid|exists:classes,id',
            'tipo_prova'    => 'required|in:teste,exame,ficha,recuperacao',
            'titulo'        => 'nullable|string|max:255',
            'observacoes'   => 'nullable|string',
            'data_inicio'   => 'required|date|before:data_limite',
            'data_limite'   => 'required|date|after:now',
            'periodo'       => 'required|string|max:20',
        ];
    }

    /**
     * Formata um prazo para exibição na lista.
     */
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
            'can'              => [
                'update' => auth()->user()->can('update', $prazo),
                'delete' => auth()->user()->can('delete', $prazo),
            ],
        ];
    }

    /**
     * Formata um prazo para a página de detalhes.
     */
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
            'can'             => [
                'update' => auth()->user()->can('update', $prazo),
                'delete' => auth()->user()->can('delete', $prazo),
            ],
        ];
    }

    /**
     * Busca professores relacionados ao prazo.
     *   Filtra pela instituição do prazo.
     */
    private function buscarProfessores(PrazoProva $prazo)
    {
        $instituicaoId = $prazo->instituicao_id;

        $base = Professor::with('user')
            ->whereHas('user', fn($q) => $q->where('instituicao_id', $instituicaoId));

        if ($prazo->disciplina_id) {
            return $base
                ->whereExists(function ($query) use ($prazo) {
                    $query->select(DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                        ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
                })
                ->get();
        }

        return $base->get();
    }
}