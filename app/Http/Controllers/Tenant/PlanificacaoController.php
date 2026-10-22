<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PeriodoProva;
use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Disciplina;
use App\Models\Tenant\Classe;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Planificacao;
use App\Models\Tenant\PlanificacaoVersao;
use App\Models\Tenant\Professor;
use App\Services\Tenant\NotificacaoPlanificacaoService;
use App\Services\Tenant\PlanificacaoStorageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

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

    private function resolverUserRole($user): string
    {
        if ($user->hasRole('Aluno')) return 'aluno';
        if ($user->hasRole('Professor')) return 'professor';
        return 'staff';
    }

    private function cursosDaInstituicao(?string $instituicaoId): array
    {
        return CursoTutelado::query()
            ->whereHas('instituicaoCurso', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with('instituicaoCurso.curso:id,nome')
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'nome' => $c->instituicaoCurso?->curso?->nome ?? 'Curso',
            ])
            ->sortBy('nome')
            ->values()
            ->toArray();
    }

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request)
    {
        $user = auth()->user();
        $instituicaoId = $this->getInstituicaoId();
        $anoAtivo = AnoLectivo::activo();
        $anoLetivoId = $request->input('ano_letivo_id') ?? $anoAtivo?->id;

        $query = Planificacao::query()->where('instituicao_id', $instituicaoId);

        if ($anoLetivoId)                          $query->where('ano_letivo_id', $anoLetivoId);
        if ($request->filled('curso_id'))          $query->where('curso_id', $request->input('curso_id'));
        if ($request->filled('disciplina_id'))     $query->where('disciplina_id', $request->input('disciplina_id'));
        if ($request->filled('classe_id'))         $query->where('classe_id', $request->input('classe_id'));
        if ($request->filled('periodo'))           $query->where('periodo', $request->input('periodo'));

        $this->aplicarFiltroPorRole($query, $user);

        $raw = $query->with(['disciplina', 'curso.instituicaoCurso.curso'])->get();

        $disciplinas = $raw
            ->groupBy('disciplina_id')
            ->map(function ($grupo) {
                $primeira = $grupo->first();
                return [
                    'id'                 => $primeira->disciplina_id,
                    'nome'               => $primeira->disciplina?->nome ?? 'Sem disciplina',
                    'sigla'              => $primeira->disciplina?->sigla,
                    'total'              => $grupo->count(),
                    'total_classes'      => $grupo->pluck('classe_id')->unique()->count(),
                    'total_cursos'       => $grupo->pluck('curso_id')->filter()->unique()->count(),
                    'ultima_atualizacao' => $grupo->max('updated_at')
                        ? Carbon::parse($grupo->max('updated_at'))->format('d/m/Y H:i')
                        : '—',
                ];
            })
            ->sortBy('nome')
            ->values();

        return Inertia::render('tenant/planificacoes/index', [
            'disciplinas'   => $disciplinas,
            'cursos'        => $this->cursosDaInstituicao($instituicaoId),
            'filters'       => $request->only(['ano_letivo_id', 'curso_id', 'disciplina_id', 'classe_id', 'periodo']),
            'classes'       => Classe::orderBy('nome')->get(['id', 'nome']),
            'anosLectivos'  => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
            'anoAtivoId'    => $anoLetivoId,
            'periodos'      => PeriodoProva::valores(),
            'userRole'      => $this->resolverUserRole($user),
            'can'           => ['create' => $user->can('create', Planificacao::class)],
        ]);
    }

    // ============================================================
    // POR DISCIPLINA
    // ============================================================

    public function porDisciplina(Request $request, string $disciplinaId)
    {
        $user = auth()->user();
        $instituicaoId = $this->getInstituicaoId();
        $anoAtivo = AnoLectivo::activo();
        $disciplina = Disciplina::findOrFail($disciplinaId);
        $anoLetivoId = $request->input('ano_letivo_id') ?? $anoAtivo?->id;

        $query = Planificacao::query()
            ->with(['disciplina', 'classe', 'anoLectivo', 'criador', 'curso.instituicaoCurso.curso', 'versoes'])
            ->where('instituicao_id', $instituicaoId)
            ->where('disciplina_id', $disciplinaId);

        if ($anoLetivoId)                      $query->where('ano_letivo_id', $anoLetivoId);
        if ($request->filled('curso_id'))      $query->where('curso_id', $request->input('curso_id'));
        if ($request->filled('classe_id'))     $query->where('classe_id', $request->input('classe_id'));
        if ($request->filled('periodo'))       $query->where('periodo', $request->input('periodo'));

        $this->aplicarFiltroPorRole($query, $user);

        $planificacoes = $query
            ->orderBy('classe_id')
            ->orderBy('periodo')
            ->get()
            ->map(fn (Planificacao $p) => $this->formatarParaLista($p))
            ->values();

        return Inertia::render('tenant/planificacoes/disciplina', [
            'disciplina'    => ['id' => $disciplina->id, 'nome' => $disciplina->nome, 'sigla' => $disciplina->sigla],
            'planificacoes' => $planificacoes,
            'cursos'        => $this->cursosDaInstituicao($instituicaoId),
            'anosLectivos'  => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
            'anoAtivoId'    => $anoLetivoId,
            'filters'       => $request->only(['ano_letivo_id', 'curso_id', 'classe_id', 'periodo']),
            'userRole'      => $this->resolverUserRole($user),
            'can'           => ['create' => $user->can('create', Planificacao::class)],
        ]);
    }

    // ============================================================
    // CREATE
    // ============================================================

    public function create(Request $request)
    {
        $anoAtivo = AnoLectivo::activo();
        $instituicaoId = $this->getInstituicaoId();

        return Inertia::render('tenant/planificacoes/create', [
            'cursos'        => $this->cursosDaInstituicao($instituicaoId),
            'disciplinas'   => Disciplina::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'classes'       => Classe::orderBy('nome')->get(['id', 'nome']),
            'anosLectivos'  => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
            'periodos'      => PeriodoProva::valores(),
            'anoAtivoId'    => $anoAtivo?->id,
        ]);
    }

    // ============================================================
    // STORE — transação única
    // ============================================================

    public function store(Request $request)
    {
        Log::info('[PLANIFICACAO STORE] Início', [
            'user_id'        => auth()->id(),
            'instituicao_id' => $this->getInstituicaoId(),
            'payload'        => $request->except(['ficheiro']),
            'ficheiro'       => $request->hasFile('ficheiro') ? [
                'name' => $request->file('ficheiro')->getClientOriginalName(),
                'size' => $request->file('ficheiro')->getSize(),
                'mime' => $request->file('ficheiro')->getMimeType(),
            ] : 'SEM FICHEIRO',
        ]);

        $instituicaoId = $this->getInstituicaoId();

        $validated = $request->validate([
            'ano_letivo_id' => ['required', 'uuid'],
            'curso_id'      => ['required', 'uuid', 'exists:curso_tutelado,id'],
            'disciplina_id' => ['required', 'uuid'],
            'classe_id'     => ['required', 'uuid', 'exists:classes,id'],
            'periodo'       => ['required', 'string', 'max:50'],
            'titulo'        => ['nullable', 'string', 'max:255'],
            'descricao'     => ['nullable', 'string', 'max:2000'],
            'ficheiro'      => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ], [
            'ano_letivo_id.required' => 'Selecione o ano letivo.',
            'curso_id.required'      => 'Selecione o curso.',
            'curso_id.exists'        => 'O curso selecionado não existe.',
            'disciplina_id.required' => 'Selecione a disciplina.',
            'classe_id.required'     => 'Selecione a classe.',
            'periodo.required'       => 'Selecione o período.',
            'ficheiro.required'      => 'Anexe um ficheiro.',
            'ficheiro.mimes'         => 'Apenas ficheiros PDF, DOC ou DOCX são aceites.',
            'ficheiro.max'           => 'O ficheiro não pode ter mais de 20 MB.',
        ]);

        Log::info('[PLANIFICACAO STORE] Validação OK', $validated);

        try {
            $resultado = DB::transaction(function () use ($validated, $instituicaoId, $request) {
                $planificacao = Planificacao::firstOrNew([
                    'ano_letivo_id' => $validated['ano_letivo_id'],
                    'curso_id'      => $validated['curso_id'],
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

                Log::info('[PLANIFICACAO STORE] Versão guardada', [
                    'versao_id'        => $versao->id,
                    'versao'           => $versao->versao,
                    'encriptado'       => $versao->encriptado,
                    'comprimido'       => $versao->comprimido,
                    'tamanho_original' => $versao->tamanho_original_bytes,
                    'tamanho_final'    => $versao->tamanho_bytes,
                ]);

                return [
                    'planificacao'   => $planificacao,
                    'versao'         => $versao,
                    'primeiraVersao' => $primeiraVersao,
                ];
            });

            Log::info('[PLANIFICACAO STORE] Transação commitou OK', [
                'planificacao_id' => $resultado['planificacao']->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[PLANIFICACAO STORE] ERRO REAL', [
                'tipo'     => get_class($e),
                'mensagem' => $e->getMessage(),
                'ficheiro' => $e->getFile() . ':' . $e->getLine(),
                'trace'    => $e->getTraceAsString(),
            ]);

            throw ValidationException::withMessages([
                'geral' => 'Erro ao guardar: ' . $e->getMessage(),
            ]);
        }

        try {
            $this->notificacao->notificarPublicacao(
                $resultado['planificacao']->fresh(['disciplina', 'classe', 'anoLectivo', 'curso.instituicaoCurso.curso']),
                $resultado['versao']->versao,
                $resultado['primeiraVersao']
            );
            Log::info('[PLANIFICACAO STORE] Notificação enviada', [
                'planificacao_id' => $resultado['planificacao']->id,
                'versao'          => $resultado['versao']->versao,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PLANIFICACAO STORE] Notificação falhou (save OK)', [
                'planificacao_id' => $resultado['planificacao']->id,
                'erro'            => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('tenant.dashboard.planificacoes.show', $resultado['planificacao']->id)
            ->with('success', $resultado['primeiraVersao']
                ? 'Planificação publicada com sucesso.'
                : 'Nova versão publicada com sucesso.');
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(Planificacao $planificacao)
    {
        $planificacao->load([
            'disciplina', 'classe', 'anoLectivo', 'criador',
            'curso.instituicaoCurso.curso', 'versoes.autor',
        ]);

        $user = auth()->user();

        return Inertia::render('tenant/planificacoes/show', [
            'planificacao' => $this->formatarParaDetalhe($planificacao),
            'userRole'     => $this->resolverUserRole($user),
            'can'          => [
                'update' => $user->can('update', $planificacao),
                'delete' => $user->can('delete', $planificacao),
            ],
        ]);
    }

    // ============================================================
    // PREVIEW
    // ============================================================

    public function preview(PlanificacaoVersao $versao)
    {
        $this->authorize('view', $versao->planificacao);

        if (! $versao->eh_pdf) {
            abort(400, 'Apenas ficheiros PDF têm pré-visualização.');
        }

        $conteudo = $this->storage->conteudo($versao);
        if ($conteudo === null) {
            Log::warning('[PlanificacaoController] Preview falhou', [
                'versao_id'  => $versao->id,
                'encriptado' => $versao->encriptado,
            ]);
            abort(404, 'Ficheiro não encontrado ou falha na desencriptação.');
        }

        Log::info('[PlanificacaoController] Preview', [
            'versao_id'   => $versao->id,
            'nome'        => $versao->nome_original,
            'bytes_final' => strlen($conteudo),
        ]);

        return response($conteudo, 200, [
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

        $conteudo = $this->storage->conteudo($versao);
        if ($conteudo === null) {
            Log::warning('[PlanificacaoController] Download falhou', [
                'versao_id'  => $versao->id,
                'encriptado' => $versao->encriptado,
            ]);
            abort(404, 'Ficheiro não encontrado ou falha na desencriptação.');
        }

        Log::info('[PlanificacaoController] Download', [
            'versao_id'     => $versao->id,
            'nome_original' => $versao->nome_original,
            'bytes_final'   => strlen($conteudo),
            'encriptado'    => $versao->encriptado,
            'comprimido'    => $versao->comprimido,
        ]);

        return response()->streamDownload(
            fn () => print($conteudo),
            $versao->nome_original,
            ['Content-Type' => $versao->mime_type ?? 'application/octet-stream'],
        );
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Planificacao $planificacao)
    {
        try {
            DB::transaction(function () use ($planificacao) {
                $this->storage->apagarTodasVersoes($planificacao);
                $planificacao->delete();
            });

            Log::info('[PlanificacaoController] Planificação apagada', [
                'planificacao_id' => $planificacao->id,
                'user_id'         => auth()->id(),
            ]);

            return redirect()
                ->route('tenant.dashboard.planificacoes.index')
                ->with('success', 'Planificação removida com sucesso.');
        } catch (\Throwable $e) {
            Log::error('[PlanificacaoController] Erro ao apagar', [
                'planificacao_id' => $planificacao->id,
                'erro'            => $e->getMessage(),
            ]);

            return back()->with('error', 'Erro ao apagar planificação.');
        }
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function aplicarFiltroPorRole($query, $user): void
    {
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
                        ->whereColumn('curso_classe.curso_tutelado_id', 'planificacoes.curso_id')
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
                        ->join('classe_turno_disciplina', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->whereColumn('curso_classe.classe_id', 'planificacoes.classe_id')
                        ->whereColumn('classe_turno_disciplina.disciplina_id', 'planificacoes.disciplina_id')
                        ->whereColumn('curso_classe.curso_tutelado_id', 'planificacoes.curso_id')
                        ->where('turma_aluno.aluno_id', $aluno->id)
                        ->where('turma_aluno.activo', true);
                });
            }
        }
    }

    private function formatarParaLista(Planificacao $p): array
    {
        $versaoAtual = $p->versoes->firstWhere('versao', $p->versao_atual) ?? $p->versoes->first();

        return [
            'id'                 => $p->id,
            'titulo'             => $p->titulo ?? 'Planificação',
            'disciplina'         => $p->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'             => $p->classe?->only(['id', 'nome']),
            'curso'              => $p->curso?->instituicaoCurso?->curso?->nome,
            'ano_letivo'         => $p->anoLectivo?->nome,
            'periodo'            => $p->periodo,
            'versao'             => $p->versao_atual,
            'tem_ficheiro'       => $versaoAtual !== null,
            'eh_pdf'             => $versaoAtual?->eh_pdf ?? false,
            'tamanho'            => $versaoAtual?->tamanho_formatado,
            'tamanho_original'   => $versaoAtual?->tamanho_original_formatado,
            'reducao_percentual' => $versaoAtual?->reducao_percentual,
            'encriptado'         => $versaoAtual?->encriptado ?? false,
            'comprimido'         => $versaoAtual?->comprimido ?? false,
            'atualizada_em'      => $p->updated_at?->format('d/m/Y H:i'),
            'criada_por'         => $p->criador?->nome,
        ];
    }

    private function formatarParaDetalhe(Planificacao $p): array
    {
        return [
            'id'            => $p->id,
            'titulo'        => $p->titulo,
            'descricao'     => $p->descricao,
            'disciplina'    => $p->disciplina?->only(['id', 'nome', 'sigla']),
            'classe'        => $p->classe?->only(['id', 'nome']),
            'curso'         => $p->curso?->instituicaoCurso?->curso?->nome,
            'ano_letivo'    => $p->anoLectivo?->only(['id', 'nome']),
            'periodo'       => $p->periodo,
            'versao_atual'  => $p->versao_atual,
            'criada_por'    => $p->criador?->nome,
            'criada_em'     => $p->created_at?->format('d/m/Y H:i'),
            'atualizada_em' => $p->updated_at?->format('d/m/Y H:i'),
            'versoes' => $p->versoes->map(fn (PlanificacaoVersao $v) => [
                'id'                    => $v->id,
                'versao'                => $v->versao,
                'nome_original'         => $v->nome_original,
                'tamanho'               => $v->tamanho_formatado,
                'tamanho_original'      => $v->tamanho_original_formatado,
                'reducao_bytes'         => $v->reducao_bytes,
                'reducao_percentual'    => $v->reducao_percentual,
                'extensao'              => $v->extensao,
                'eh_pdf'                => $v->eh_pdf,
                'encriptado'            => $v->encriptado,
                'comprimido'            => $v->comprimido,
                'algoritmo_encriptacao' => $v->algoritmo_encriptacao,
                'uploaded_by'           => $v->autor?->nome,
                'uploaded_em'           => $v->created_at?->format('d/m/Y H:i'),
                'is_atual'              => $v->versao === $p->versao_atual,
            ])->values(),
        ];
    }
}



