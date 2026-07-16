<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagamento\StorePagamentoRequest;
use App\Http\Requests\Pagamento\UpdatePagamentoRequest;
use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\Pagamento;
use App\Models\PagamentoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Pagamento::class);

        $pagamentos = Pagamento::query()
            ->where('instituicao_id', $request->user()->instituicao_id)
            ->with(['aluno.user:id,nome'])
            ->when($request->aluno_id, fn ($q) => $q->where('aluno_id', $request->aluno_id))
            ->when($request->data_inicio, fn ($q) => $q->whereDate('data_pagamento', '>=', $request->data_inicio))
            ->when($request->data_fim, fn ($q) => $q->whereDate('data_pagamento', '<=', $request->data_fim))
            ->orderByDesc('data_pagamento')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Pagamento $p) => [
                'id' => $p->id,
                'aluno' => $p->aluno->user->nome,
                'valor_total' => $p->valor_total,
                'metodo' => $p->metodo,
                'referencia' => $p->referencia,
                'data_pagamento' => $p->data_pagamento->format('d/m/Y'),
            ]);

        return Inertia::render('pagamentos/index', [
            'pagamentos' => $pagamentos,
            'filtros' => $request->only(['aluno_id', 'data_inicio', 'data_fim']),
        ]);
    }

    public function create(Request $request)
    {
        // $this->authorize('create', Pagamento::class);

        return Inertia::render('pagamentos/create', [
            'alunos' => Aluno::query()
                ->whereHas('user', fn ($q) => $q->where('instituicao_id', $request->user()->instituicao_id))
                ->with('user:id,nome')
                ->activos()
                ->get(['id', 'user_id'])
                ->map(fn (Aluno $aluno) => [
                    'id' => $aluno->id,
                    'nome' => $aluno->user->nome,
                ]),
            'itensPagaveis' => ItemPagavel::query()
                ->where('instituicao_id', $request->user()->instituicao_id)
                ->ativos()
                ->get(['id', 'nome', 'valor', 'frequencia', 'curso_classe_id']),
            'paidRecord' => Inertia::optional(fn () => $request->filled('aluno_id')
                ? $this->paidRecordDoAluno($request->aluno_id)
                : []),
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
        });

        return redirect()->route('pagamentos.index')->with('success', 'Pagamento registado com sucesso.');
    }

    public function show(Pagamento $pagamento)
    {
        // $this->authorize('view', $pagamento);

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
        // $this->authorize('update', $pagamento);

        return Inertia::render('pagamentos/edit', [
            'pagamento' => $pagamento->load('itens.itemPagavel', 'aluno'),
        ]);
    }

    public function update(UpdatePagamentoRequest $request, Pagamento $pagamento)
    {
        $pagamento->update($request->validated());

        return back()->with('success', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Pagamento $pagamento)
    {
        // $this->authorize('delete', $pagamento);

        $pagamento->delete();

        return back()->with('success', 'Pagamento anulado com sucesso.');
    }
}
