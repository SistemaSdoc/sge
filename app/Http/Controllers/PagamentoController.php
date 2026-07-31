<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagamento\StorePagamentoRequest;
use App\Http\Requests\Pagamento\UpdatePagamentoRequest;
use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\ItemPagavel;
use App\Models\Pagamento;
use App\Models\PagamentoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function index(Request $request)
    {
        $instituicaoId = $request->user()->instituicao_id;

        Log::info('PagamentoController@index - início', [
            'user_id' => $request->user()->id,
            'instituicao_id' => $instituicaoId,
            'query_params' => $request->query(),
        ]);

        $query = Pagamento::query()
            ->where('instituicao_id', $instituicaoId)
            ->with(['aluno.user:id,nome']);

        // Filtro por aluno
        if ($request->filled('aluno_id')) {
            $query->where('aluno_id', $request->aluno_id);
            Log::debug('PagamentoController@index - filtro por aluno', ['aluno_id' => $request->aluno_id]);
        }

        // Filtro por data
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_pagamento', '>=', $request->data_inicio);
            Log::debug('PagamentoController@index - filtro data_inicio', ['data_inicio' => $request->data_inicio]);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_pagamento', '<=', $request->data_fim);
            Log::debug('PagamentoController@index - filtro data_fim', ['data_fim' => $request->data_fim]);
        }

        // Filtro por curso_classe (através dos itens)
        if ($request->filled('curso_classe_id')) {
            $query->whereHas('itens', function ($q) use ($request) {
                $q->whereHas('itemPagavel', function ($sub) use ($request) {
                    $sub->where('curso_classe_id', $request->curso_classe_id)
                        ->orWhereNull('curso_classe_id');
                });
            });
            Log::debug('PagamentoController@index - filtro curso_classe_id', ['curso_classe_id' => $request->curso_classe_id]);
        }

        $pagamentos = $query
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

        $cursosClasses = CursoClasse::query()
            ->whereHas('cursoTutelado.instituicaoCurso', function ($q) use ($instituicaoId) {
                $q->where('instituicao_id', $instituicaoId);
            })
            ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
            ->get()
            ->map(fn (CursoClasse $cc) => [
                'id' => $cc->id,
                'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome . ' — ' . $cc->classe->nome,
            ]);

        return Inertia::render('pagamentos/index', [
            'pagamentos' => $pagamentos,
            'filtros' => $request->only(['aluno_id', 'data_inicio', 'data_fim', 'curso_classe_id']),
            'cursosClasses' => $cursosClasses,
        ]);
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
                // --- CORREÇÃO: carregar relacionamento e obter IDs via relação ---
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

    // Aplicar filtro usando os IDs obtidos (via relação ou diretos)
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
        // Fallback: turma sem vínculo → apenas itens universais
        $itensQuery->whereNull('curso_classe_id');
        Log::debug('PagamentoController@create - sem vínculo – a mostrar apenas itens universais');
    }

    $itensPagaveis = $itensQuery->get(['id', 'nome', 'valor', 'frequencia', 'curso_classe_id']);

    // Log detalhado de cada item retornado
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

    // Lista de alunos para o combobox
    $alunos = Aluno::query()
        ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicaoId))
        ->with('user:id,nome')
        ->activos()
        ->get(['id', 'user_id'])
        ->map(fn (Aluno $a) => [
            'id' => $a->id,
            'nome' => $a->user->nome,
        ]);

    // PaidRecord
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
        });

        return redirect()->route('pagamentos.index')->with('success', 'Pagamento registado com sucesso.');
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
        Log::warning('PagamentoController@destroy - anulando pagamento', [
            'pagamento_id' => $pagamento->id,
            'aluno_id' => $pagamento->aluno_id,
            'valor_total' => $pagamento->valor_total,
        ]);

        $pagamento->delete();

        Log::info('PagamentoController@destroy - pagamento anulado', ['pagamento_id' => $pagamento->id]);

        return back()->with('success', 'Pagamento anulado com sucesso.');
    }
}