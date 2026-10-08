<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Disciplina;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Planificacao;
use App\Models\Tenant\PlanificacaoVersao;
use App\Models\Tenant\Professor;
use App\Services\Tenant\NotificacaoPlanificacaoService;
use App\Services\Tenant\PlanificacaoStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanificacaoController extends Controller
{
    public function __construct(
        private readonly PlanificacaoStorageService $storage,
        private readonly NotificacaoPlanificacaoService $notificacao,
    ) {
        $this->authorizeResource(Planificacao::class, 'planificacao');
    }

    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    // ============================================================
    // LISTAGEM
    // ============================================================

    public function index(Request $request)
    {
        $user = auth()->user();
        $instituicaoId = $this->getInstituicaoId();
        $anoAtivo = AnoLectivo::activo();

        $query = Planificacao::query()
            ->with(['disciplina', 'classe', 'anoLectivo', 'criador'])
            ->where('instituicao_id', $instituicaoId);

        // Filtro por ano letivo
        if ($request->filled('ano_letivo_id')) {
            $query->where('ano_letivo_id', $request->input('ano_letivo_id'));
        } elseif ($anoAtivo) {
            $query->where('ano_letivo_id', $anoAtivo->id);
        }

        // Filtros opcionais
        if ($request->filled('disciplina_id')) {
            $query->where('disciplina_id', $request->input('disciplina_id'));
        }
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->input('classe_id'));
        }
        if ($request->filled('periodo')) {
            $query->where('periodo', $request->input('periodo'));
        }

        // ───────────────────────────────────────────────────
        // Filtro por role
        // ───────────────────────────────────────────────────
        if ($user->hasRole('Professor')) {
            $professor = Professor::where('user_id', $user->id)->first();

            if ($professor) {
                $query->whereExists(function ($sub) use ($professor) {
                    $sub->select(DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('classe_turno_disciplina.disciplina_id', 'planificacoes.disciplina_id')
                        ->whereColumn('curso_classe.classe_id', 'planificacoes.classe_id')
                        ->where('turma_disciplina_professor.professor_id', $professor->id);
                });
            }
        } elseif ($user->hasRole('Aluno')) {
            $aluno = $user->aluno;

            if ($aluno) {
                $query->whereExists(function ($sub) use ($aluno) {
                    $sub->select(DB::raw(1))
                        ->from('turma_aluno')
                        ->join('turmas', 'turma_aluno.turma_id', '=', 'turmas.id')
                        ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('curso_classe.classe_id', 'planificacoes.classe_id')
                        ->where('turma_aluno.aluno_id', $aluno->id)
                        ->where('turma_aluno.activo', true);
                });
            }
        }

        $planificacoes = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Planificacao $p) => $this->formatarParaLista($p));

        return Inertia::render('tenant/planificacoes/index', [
            'planificacoes' => $planificacoes,
            'filters'       => $request->only(['ano_letivo_id', 'disciplina_id', 'classe_id', 'periodo']),
            'disciplinas'   => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'       => Classe::orderBy('nome')->get(['id', 'nome']),
            'anosLectivos'  => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
            'anoAtivoId'    => $anoAtivo?->id,
            'periodos'      => \App\Enums\PeriodoProva::valores(),
            'can' => [
                'create' => $user->can('create', Planificacao::class),
            ],
        ]);
    }

    // ============================================================
    // FORMULÁRIO DE UPLOAD
    // ============================================================

    public function create(Request $request)
    {
        $instituicaoId = $this->getInstituicaoId();
        $anoAtivo = AnoLectivo::activo();

        return Inertia::render('tenant/planificacoes/create', [
            'disciplinas'  => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'      => Classe::orderBy('nome')->get(['id', 'nome']),
            'anosLectivos' => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
            'periodos'     => \App\Enums\PeriodoProva::valores(),
            'anoAtivoId'   => $anoAtivo?->id,
        ]);
    }

    // ============================================================
    // GUARDAR (criar ou nova versão)
    // ============================================================

    public function store(Request $request)
    {
        $instituicaoId = $this->getInstituicaoId();

        $validated = $request->validate([
            'ano_letivo_id' => ['required', 'uuid'],
            'disciplina_id' => ['required', 'uuid'],
            'classe_id'     => ['required', 'uuid', 'exists:classes,id'],
            'periodo'       => ['required', 'string', 'max:50'],
            'titulo'        => ['nullable', 'string', 'max:255'],
            'descricao'     => ['nullable', 'string', 'max:2000'],
            'ficheiro'      => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'], // 20 MB
        ], [
            'ano_letivo_id.required' => 'Selecione o ano letivo.',
            'disciplina_id.required' => 'Selecione a disciplina.',
            'classe_id.required'     => 'Selecione a classe.',
            'periodo.required'       => 'Selecione o período.',
            'ficheiro.required'      => 'Anexe um ficheiro.',
            'ficheiro.mimes'         => 'Apenas ficheiros PDF, DOC ou DOCX são aceites.',
            'ficheiro.max'           => 'O ficheiro não pode ter mais de 20 MB.',
        ]);

        try {
            $resultado = DB::transaction(function () use ($validated, $instituicaoId, $request) {
                // Procura planificação existente com a chave
                $planificacao = Planificacao::firstOrNew([
                    'ano_letivo_id' => $validated['ano_letivo_id'],
                    'disciplina_id' => $validated['disciplina_id'],
                    'classe_id'     => $validated['classe_id'],
                    'periodo'       => $validated['periodo'],
                ]);

                $primeiraVersao = ! $planificacao->exists;

                if ($primeiraVersao) {
                    $planificacao->fill([
                        'instituicao_id' => $instituicaoId,
                        'titulo'         => $validated['titulo'] ?? null,
                        'descricao'      => $validated['descricao'] ?? null,
                        'versao_atual'   => 0,
                        'created_by'     => auth()->id(),
                    ])->save();
                } else {
                    // Atualiza metadados editáveis
                    $planificacao->fill([
                        'titulo'    => $validated['titulo'] ?? $planificacao->titulo,
                        'descricao' => $validated['descricao'] ?? $planificacao->descricao,
                    ])->save();
                }

                $versao = $this->storage->guardarNovaVersao(
                    $planificacao,
                    $request->file('ficheiro'),
                    auth()->id()
                );

                return [
                    'planificacao'    => $planificacao,
                    'versao'          => $versao,
                    'primeiraVersao'  => $primeiraVersao,
                ];
            });

            // Notificar fora da transação
            $this->notificacao->notificarPublicacao(
                $resultado['planificacao']->fresh(['disciplina', 'classe', 'anoLectivo']),
                $resultado['versao']->versao,
                $resultado['primeiraVersao']
            );

            Log::info('Planificação guardada', [
                'planificacao_id' => $resultado['planificacao']->id,
                'versao'          => $resultado['versao']->versao,
                'primeira'        => $resultado['primeiraVersao'],
                'user_id'         => auth()->id(),
            ]);

            return redirect()
                ->route('tenant.dashboard.planificacoes.show', $resultado['planificacao']->id)
                ->with('success', $resultado['primeiraVersao']
                    ? 'Planificação publicada com sucesso.'
                    : 'Nova versão publicada com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Erro ao guardar planificação', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Erro ao guardar planificação: ' . $e->getMessage())
                ->withInput();
        }
    }

    // ============================================================
    // DETALHE
    // ============================================================

    public function show(Planificacao $planificacao)
    {
        $planificacao->load([
            'disciplina',
            'classe',
            'anoLectivo',
            'criador',
            'versoes.autor',
        ]);

        $user = auth()->user();

        return Inertia::render('tenant/planificacoes/show', [
            'planificacao' => $this->formatarParaDetalhe($planificacao),
            'can' => [
                'update' => $user->can('update', $planificacao),
                'delete' => $user->can('delete', $planificacao),
            ],
        ]);
    }

    // ============================================================
    // PREVIEW INLINE (PDF no browser)
    // ============================================================

    public function preview(PlanificacaoVersao $versao)
    {
        $this->authorize('view', $versao->planificacao);

        if (! $versao->eh_pdf) {
            abort(400, 'Apenas ficheiros PDF têm pré-visualização.');
        }

        $caminho = $this->storage->caminhoAbsoluto($versao);

        if (! $caminho || ! file_exists($caminho)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response()->file($caminho, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $versao->nome_original . '"',
            'X-Frame-Options'     => 'SAMEORIGIN',
        ]);
    }

    // ============================================================
    // DOWNLOAD
    // ============================================================

    public function download(PlanificacaoVersao $versao)
    {
        $this->authorize('view', $versao->planificacao);

        $caminho = $this->storage->caminhoAbsoluto($versao);

        if (! $caminho || ! file_exists($caminho)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response()->download($caminho, $versao->nome_original);
    }

    // ============================================================
    // APAGAR
    // ============================================================

    public function destroy(Planificacao $planificacao)
    {
        try {
            DB::transaction(function () use ($planificacao) {
                $this->storage->apagarTodasVersoes($planificacao);
                $planificacao->delete();
            });

            Log::info('Planificação apagada', [
                'planificacao_id' => $planificacao->id,
                'user_id'         => auth()->id(),
            ]);

            return redirect()
                ->route('tenant.dashboard.planificacoes.index')
                ->with('success', 'Planificação removida com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Erro ao apagar planificação', [
                'planificacao_id' => $planificacao->id,
                'erro'            => $e->getMessage(),
            ]);

            return back()->with('error', 'Erro ao apagar planificação.');
        }
    }

    // ============================================================
    // FORMATTERS
    // ============================================================

    private function formatarParaLista(Planificacao $p): array
    {
        $versaoAtual = $p->versoes->firstWhere('versao', $p->versao_atual)
            ?? $p->versoes->first();

        return [
            'id'           => $p->id,
            'titulo'       => $p->titulo ?? 'Planificação',
            'disciplina'   => $p->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'       => $p->classe?->only(['id', 'nome']),
            'ano_letivo'   => $p->anoLectivo?->nome,
            'periodo'      => $p->periodo,
            'versao'       => $p->versao_atual,
            'tem_ficheiro' => $versaoAtual !== null,
            'eh_pdf'       => $versaoAtual?->eh_pdf ?? false,
            'atualizada_em'=> $p->updated_at?->format('d/m/Y H:i'),
            'criada_por'   => $p->criador?->nome,
        ];
    }

    private function formatarParaDetalhe(Planificacao $p): array
    {
        return [
            'id'           => $p->id,
            'titulo'       => $p->titulo,
            'descricao'    => $p->descricao,
            'disciplina'   => $p->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'       => $p->classe?->only(['id', 'nome']),
            'ano_letivo'   => $p->anoLectivo?->only(['id', 'nome']),
            'periodo'      => $p->periodo,
            'versao_atual' => $p->versao_atual,
            'criada_por'   => $p->criador?->nome,
            'criada_em'    => $p->created_at?->format('d/m/Y H:i'),
            'atualizada_em'=> $p->updated_at?->format('d/m/Y H:i'),
            'versoes'      => $p->versoes->map(fn (PlanificacaoVersao $v) => [
                'id'             => $v->id,
                'versao'         => $v->versao,
                'nome_original'  => $v->nome_original,
                'tamanho'        => $v->tamanho_formatado,
                'extensao'       => $v->extensao,
                'eh_pdf'         => $v->eh_pdf,
                'uploaded_by'    => $v->autor?->nome,
                'uploaded_em'    => $v->created_at?->format('d/m/Y H:i'),
                'is_atual'       => $v->versao === $p->versao_atual,
            ])->values(),
        ];
    }
}