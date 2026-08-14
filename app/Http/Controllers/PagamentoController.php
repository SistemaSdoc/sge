<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagamento\StorePagamentoRequest;
use App\Http\Requests\Pagamento\UpdatePagamentoRequest;
use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\ItemPagavel;
use App\Models\Pagamento;
use App\Models\PagamentoItem;
use App\Models\Turma;
use App\Notifications\PropinaEmAtrasoNotification;
use App\Services\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function index(Request $request)
    {
        $instituicaoId = $request->user()->instituicao_id;

        Log::info('PagamentoController@index - início', [
            'user_id' => $request->user()->id,
            'instituicao_id' => $instituicaoId,
        ]);

        $pagamentos = Pagamento::query()
            ->where('instituicao_id', $instituicaoId)
            ->with(['aluno.user:id,nome'])
            ->orderByDesc('data_pagamento')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Pagamento $p) {
                $classes = $p->itens()
                    ->with('itemPagavel.cursoClasse.classe')
                    ->get()
                    ->pluck('itemPagavel.cursoClasse.classe.nome')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return [
                    'id' => $p->id,
                    'aluno' => $p->aluno->user->nome,
                    'valor_total' => $p->valor_total,
                    'metodo' => $p->metodo,
                    'referencia' => $p->referencia,
                    'data_pagamento' => $p->data_pagamento->format('d/m/Y'),
                    'classes' => $classes ?: 'Geral',
                ];
            });

        Log::info('PagamentoController@index - total de pagamentos', ['total' => $pagamentos->total()]);

        $turmas = Turma::query()
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($q) use ($instituicaoId) {
                $q->where('instituicao_id', $instituicaoId);
            })
            ->with(['cursoClasseTurno.cursoClasse.classe'])
            ->get()
            ->map(fn (Turma $t) => [
                'id' => $t->id,
                'nome' => $t->nome.' — '.($t->cursoClasseTurno?->cursoClasse?->classe?->nome ?? ''),
            ]);

        $statusFiltro = $request->input('status_propina'); // 'pagos' | 'nao_pagos' | 'pendentes'

        $alunosPorStatus = null;

        if ($statusFiltro) {
            $alunosPorStatus = $this->alunosAgrupadosPorStatus($request, $statusFiltro);
        }

        return Inertia::render('pagamentos/index', [
            'pagamentos' => $pagamentos,
            'turmas' => $turmas,
            'can' => [
                'create' => Auth::user()->can('create', Pagamento::class),
            ],
            'filtros' => $request->only(['aluno_id', 'data_inicio', 'data_fim']),
            'statusFiltro' => $statusFiltro,
            'alunosPorStatus' => $alunosPorStatus,
        ]);
    }

    /**
     * Retorna alunos filtrados por status de propina (pagos/nao_pagos/pendentes),
     * agrupados por Classe -> Turma, considerando o ano lectivo activo.
     */
    private function alunosAgrupadosPorStatus(Request $request, string $status): array
    {
        $anoLectivoId = AnoLectivo::activo()?->id;

        $alunos = Aluno::whereIn('situacao', ['activo', 'finalista', 'reprovado'])
            ->doAnoLectivo($anoLectivoId)
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $request->user()->instituicao_id))
            ->with([
                // FIX: 'instituicao_id' precisa de ser seleccionado aqui.
                // Sem ele, $aluno->user->instituicao_id vinha null dentro do
                // VerificadorPropinaService, e a query de ItemPagavel
                // (where('instituicao_id', null)) nunca encontrava itens,
                // fazendo todos os alunos caírem como "em dia" por omissão.
                'user:id,nome,instituicao_id',
                'inscricao.candidato:id,nome',
                'inscricao.cursoClasseTurno.turno:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turmas' => fn ($q) => $q->wherePivot('activo', true)
                    ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
            ])
            ->get()
            ->filter() // remove qualquer elemento nulo da colecção antes de continuar
            ->values();

        Log::debug('PagamentoController@alunosAgrupadosPorStatus - alunos carregados', [
            'status' => $status,
            'total_alunos' => $alunos->count(),
        ]);

        $filtrados = $alunos->filter(function (Aluno $aluno) use ($status) {
            $pendencias = $this->verificador->pendenciasDoAluno($aluno);
            $emDia = empty($pendencias);
            $mesesEmAtraso = count($pendencias);

            return match ($status) {
                'pagos' => $emDia,
                'nao_pagos' => ! $emDia,
                // "pendentes" = em atraso de 1 ou mais meses (mesma condição de nao_pagos,
                // mantido separado caso queiras diferenciar limiares no futuro)
                'pendentes' => ! $emDia && $mesesEmAtraso >= 1,
                default => true,
            };
        });

        Log::debug('PagamentoController@alunosAgrupadosPorStatus - alunos filtrados', [
            'status' => $status,
            'total_filtrados' => $filtrados->count(),
        ]);

        // Agrupar por Classe -> Turma, mantendo nome, curso, turno
        $agrupado = $filtrados
            ->groupBy(fn (Aluno $aluno) => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome ?? 'Sem classe')
            ->map(function ($alunosDaClasse) {
                return $alunosDaClasse
                    ->groupBy(fn (Aluno $aluno) => $aluno->turmas->first()?->nome ?? 'Sem turma')
                    ->map(function ($alunosDaTurma) {
                        return $alunosDaTurma->map(fn (Aluno $aluno) => [
                            'id' => $aluno->id,
                            'nome' => $aluno->inscricao?->candidato?->nome ?? $aluno->user?->nome,
                            'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                            'turma' => $aluno->turmas->first()?->nome,
                            'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome,
                            'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
                        ])->values();
                    });
            });

        return $agrupado->toArray();
    }

    public function create(Request $request)
    {
        $instituicaoId = $request->user()->instituicao_id;

        Log::info('PagamentoController@create - início', [
            'instituicao_id' => $instituicaoId,
            'aluno_id' => $request->query('aluno_id'),
            'query_params' => $request->query(),
        ]);

        // Buscar aluno e turma
        $aluno = null;
        $turma = null;
        $cursoClasseId = null;
        $classeId = null;

        if ($request->filled('aluno_id')) {
            $aluno = Aluno::with('turmaActual')->find($request->aluno_id);
            if ($aluno) {
                $turma = $aluno->turmaActual->first();
                if ($turma) {
                    $turma->loadMissing(['cursoClasseTurno.cursoClasse']);
                    $cursoClasseId = $turma->curso_classe_id
                                    ?? $turma->cursoClasseTurno->curso_classe_id
                                    ?? null;
                    $classeId = $turma->classe_id
                                ?? $turma->cursoClasseTurno->cursoClasse->classe_id
                                ?? null;

                    Log::debug('PagamentoController@create - turma do aluno', [
                        'aluno_id' => $aluno->id,
                        'turma_id' => $turma->id,
                        'curso_classe_id' => $cursoClasseId,
                        'classe_id' => $classeId,
                    ]);
                } else {
                    Log::debug('PagamentoController@create - aluno sem turma', ['aluno_id' => $aluno->id]);
                }
            } else {
                Log::warning('PagamentoController@create - aluno não encontrado', ['aluno_id' => $request->aluno_id]);
            }
        }

        // Query de itens ativos
        $itensQuery = ItemPagavel::query()
            ->where('instituicao_id', $instituicaoId)
            ->ativos();

        if ($turma && ($cursoClasseId || $classeId)) {
            $itensQuery->where(function ($q) use ($cursoClasseId, $classeId) {
                // 1. Itens universais
                $q->whereNull('curso_classe_id');

                // 2. Diretamente vinculados ao curso_classe da turma
                if ($cursoClasseId) {
                    $q->orWhere('curso_classe_id', $cursoClasseId);
                }

                // 3. Vinculados a um curso_classe com a mesma classe_id da turma
                if ($classeId) {
                    $q->orWhereExists(function ($sub) use ($classeId) {
                        $sub->from('curso_classe')
                            ->whereColumn('curso_classe.id', 'itens_pagaveis.curso_classe_id')
                            ->where('curso_classe.classe_id', $classeId);
                    });
                }
            });

            Log::debug('PagamentoController@create - filtro aplicado (com vínculo)', [
                'curso_classe_id' => $cursoClasseId,
                'classe_id' => $classeId,
            ]);
        } else {
            $itensQuery->whereNull('curso_classe_id');
            Log::debug('PagamentoController@create - sem vínculo – a mostrar apenas itens universais');
        }

        $itensPagaveis = $itensQuery->get(['id', 'nome', 'valor', 'frequencia', 'curso_classe_id']);

        $itensPagaveis->each(function ($item) {
            $item->load('cursoClasse.classe');
            Log::debug('PagamentoController@create - ITEM RETORNADO', [
                'item_id' => $item->id,
                'item_nome' => $item->nome,
                'curso_classe_id' => $item->curso_classe_id,
                'classe_associada' => $item->cursoClasse?->classe?->nome ?? 'Nenhuma',
                'curso_associado' => $item->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome ?? 'Nenhum',
                'frequencia' => $item->frequencia,
                'valor' => $item->valor,
            ]);
        });

        Log::info('PagamentoController@create - total de itens retornados', [
            'total' => $itensPagaveis->count(),
            'ids' => $itensPagaveis->pluck('id')->toArray(),
        ]);

        $alunos = Aluno::query()
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with('user:id,nome')
            ->activos()
            ->get(['id', 'user_id'])
            ->map(fn (Aluno $a) => [
                'id' => $a->id,
                'nome' => $a->user->nome,
            ]);

        $paidRecord = [];
        if ($request->filled('aluno_id')) {
            $paidRecord = $this->paidRecordDoAluno($request->aluno_id);
            Log::debug('PagamentoController@create - paidRecord', ['paidRecord' => $paidRecord]);
        }

        return Inertia::render('pagamentos/create', [
            'alunos' => $alunos,
            'itensPagaveis' => $itensPagaveis,
            'paidRecord' => $paidRecord,
        ]);
    }

    private function paidRecordDoAluno(string $alunoId): array
    {
        return PagamentoItem::query()
            ->where('aluno_id', $alunoId)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id')
            ->map(fn ($linhas) => $linhas
                ->pluck('mes')
                ->filter(fn ($mes) => $mes !== null)
                ->map(fn ($mes) => (int) $mes)
                ->values()
                ->unique()
                ->values()
                ->all())
            ->toArray();
    }

    public function store(StorePagamentoRequest $request)
    {
        Log::info('PagamentoController@store - início', [
            'aluno_id' => $request->input('aluno_id'),
            'metodo' => $request->input('metodo'),
            'itens_count' => count($request->input('itens', [])),
        ]);

        DB::transaction(function () use ($request) {
            $valorTotal = 0;
            $linhasParaCriar = [];

            foreach ($request->input('itens') as $linha) {
                $item = ItemPagavel::findOrFail($linha['item_pagavel_id']);
                $valorUnitario = $linha['valor'] ?? $item->valor;
                $meses = $item->frequencia === 'mensal' ? $linha['meses'] : [0];

                foreach ($meses as $mes) {
                    $linhasParaCriar[] = [
                        'item_pagavel_id' => $item->id,
                        'aluno_id' => $request->input('aluno_id'),
                        'mes' => $mes,
                        'ano' => $linha['ano'],
                        'valor' => $valorUnitario,
                    ];
                    $valorTotal += $valorUnitario;
                }

                Log::debug('PagamentoController@store - item processado', [
                    'item_id' => $item->id,
                    'item_nome' => $item->nome,
                    'frequencia' => $item->frequencia,
                    'meses' => $meses,
                    'valor_unitario' => $valorUnitario,
                ]);
            }

            $pagamento = Pagamento::create([
                'aluno_id' => $request->input('aluno_id'),
                'instituicao_id' => $request->user()->instituicao_id,
                'registado_por' => $request->user()->id,
                'data_pagamento' => $request->input('data_pagamento'),
                'valor_total' => $valorTotal,
                'metodo' => $request->input('metodo'),
                'referencia' => $request->input('referencia'),
                'observacoes' => $request->input('observacoes'),
            ]);

            $pagamento->itens()->createMany($linhasParaCriar);

            Log::info('PagamentoController@store - pagamento registado', [
                'pagamento_id' => $pagamento->id,
                'valor_total' => $valorTotal,
                'itens_quantidade' => count($linhasParaCriar),
            ]);

            $this->resolverNotificacoesSePropinaEmDia($request->input('aluno_id'));
        });

        return redirect()->route('pagamentos.index')->with('success', 'Pagamento registado com sucesso.');
    }

    /**
     * Depois de registar um pagamento, verifica se o aluno ficou em dia
     * com as propinas. Se sim, marca como lidas as notificações de
     * "propina em atraso" que ainda estavam por ler. Se ainda houver
     * dívida (ex: pagou só parte dos meses), a notificação mantém-se.
     */
    private function resolverNotificacoesSePropinaEmDia(string $alunoId): void
    {
        Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - início', [
            'aluno_id' => $alunoId,
        ]);

        $aluno = Aluno::with('user')->find($alunoId);

        if (! $aluno || ! $aluno->user) {
            Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - aluno ou user não encontrado', [
                'aluno_id' => $alunoId,
            ]);

            return;
        }

        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - pendências após pagamento', [
            'aluno_id' => $alunoId,
            'total_pendencias' => count($pendencias),
        ]);

        if (! empty($pendencias)) {
            Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - ainda tem dívida, notificação mantém-se', [
                'aluno_id' => $alunoId,
                'meses_restantes' => count($pendencias),
            ]);

            return;
        }

        $marcadas = $aluno->user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Log::info('PagamentoController@resolverNotificacoesSePropinaEmDia - notificações resolvidas (propina paga)', [
            'aluno_id' => $alunoId,
            'user_id' => $aluno->user->id,
            'total_marcadas' => $marcadas,
        ]);
    }

    public function show(Pagamento $pagamento)
    {
        Log::info('PagamentoController@show - início', ['pagamento_id' => $pagamento->id]);

        $pagamento->load(['aluno.user:id,nome', 'registadoPor:id,nome']);

        $itens = $pagamento->itens()
            ->with('itemPagavel:id,nome,frequencia')
            ->orderBy('ano')
            ->orderBy('mes')
            ->paginate(10)
            ->through(fn ($item) => [
                'id' => $item->id,
                'nome' => $item->itemPagavel->nome,
                'frequencia' => $item->itemPagavel->frequencia,
                'mes' => $item->mes,
                'ano' => $item->ano,
                'valor' => $item->valor,
            ])
            ->withQueryString();

        Log::debug('PagamentoController@show - itens retornados', ['total' => $itens->total()]);

        return Inertia::render('pagamentos/show', [
            'pagamento' => [
                'id' => $pagamento->id,
                'aluno' => $pagamento->aluno->user->nome,
                'registado_por' => $pagamento->registadoPor->nome,
                'data_pagamento' => $pagamento->data_pagamento->format('d/m/Y'),
                'valor_total' => $pagamento->valor_total,
                'metodo' => $pagamento->metodo,
                'referencia' => $pagamento->referencia,
                'observacoes' => $pagamento->observacoes,
                'itens' => $itens,
            ],
        ]);
    }

    public function edit(Pagamento $pagamento)
    {
        Log::info('PagamentoController@edit - início', ['pagamento_id' => $pagamento->id]);

        return Inertia::render('pagamentos/edit', [
            'pagamento' => $pagamento->load('itens.itemPagavel', 'aluno'),
        ]);
    }

    public function update(UpdatePagamentoRequest $request, Pagamento $pagamento)
    {
        Log::info('PagamentoController@update - início', [
            'pagamento_id' => $pagamento->id,
            'dados' => $request->validated(),
        ]);

        $pagamento->update($request->validated());

        Log::info('PagamentoController@update - pagamento atualizado', ['pagamento_id' => $pagamento->id]);

        return back()->with('success', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Pagamento $pagamento)
    {
        // $this->authorize('delete', $pagamento);

        Log::warning('PagamentoController@destroy - anulando pagamento', [
            'pagamento_id' => $pagamento->id,
            'aluno_id' => $pagamento->aluno_id,
            'valor_total' => $pagamento->valor_total,
        ]);

        DB::transaction(function () use ($pagamento) {
            $pagamento->itens()->withTrashed()->forceDelete();
            $pagamento->delete();
        });

        Log::info('PagamentoController@destroy - pagamento anulado', ['pagamento_id' => $pagamento->id]);

        return back()->with('success', 'Pagamento anulado com sucesso.');
    }
}
