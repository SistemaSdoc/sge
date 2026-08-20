<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Propina;
use App\Models\Tenant\Turma;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PropinaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Propina::class);

        $propinas = Propina::query()
            ->with(['aluno:id,nome,numero_processo', 'itemPagavel:id,nome,tipo'])
            ->when($request->aluno_id, fn ($q) => $q->where('aluno_id', $request->aluno_id))
            ->when($request->turma_id, fn ($q) => $q->whereHas(
                'aluno.inscricaoActiva',
                fn ($sub) => $sub->where('turma_id', $request->turma_id)
            ))
            ->when($request->estado, fn ($q) => $q->where('estado', $request->estado))
            ->when($request->mes, fn ($q) => $q->where('mes', $request->mes))
            ->orderByDesc('data_vencimento')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Propina $propina) => [
                'id' => $propina->id,
                'aluno' => $propina->aluno->only(['id', 'nome', 'numero_processo']),
                'item_pagavel' => $propina->itemPagavel->only(['id', 'nome', 'tipo']),
                'mes' => $propina->mes,
                'valor_devido' => $propina->valor_devido,
                'total_pago' => $propina->totalPago(),
                'data_vencimento' => $propina->data_vencimento->format('Y-m-d'),
                'estado' => $propina->estado,
                'can' => [
                    'update' => $request->user()->can('update', $propina),
                    'delete' => $request->user()->can('delete', $propina),
                ],
            ]);

        return Inertia::render('tenant/Propina/Index', [
            'propinas' => $propinas,
            'filters' => $request->only(['aluno_id', 'turma_id', 'estado', 'mes']),
            'can' => [
                'create' => $request->user()->can('create', Propina::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Propina::class);

        return Inertia::render('tenant/Propina/Create', [
            'itensPagaveis' => ItemPagavel::where('instituicao_id', auth()->user()->instituicao_id)->get(['id', 'nome', 'tipo', 'valor_padrao']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Propina::class);

        $data = $request->validate([
            'aluno_id' => ['required', 'uuid', 'exists:alunos,id'],
            'ano_lectivo_id' => ['required', 'uuid', 'exists:ano_lectivos,id'],
            'item_pagavel_id' => ['required', 'uuid', 'exists:item_pagaveis,id'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'valor_devido' => ['required', 'numeric', 'min:0'],
            'data_vencimento' => ['required', 'date'],
        ]);

        Propina::create($data + ['estado' => 'pendente']);

        return redirect()->route('propinas.index')->with('success', 'Propina criada com sucesso.');
    }

    /**
     * Gera em massa a propina de mensalidade para todos os alunos de uma turma, num mês.
     */
    public function gerarMensalidades(Request $request)
    {
        $this->authorize('create', Propina::class);

        $data = $request->validate([
            'turma_id' => ['required', 'uuid', 'exists:turmas,id'],
            'ano_lectivo_id' => ['required', 'uuid', 'exists:ano_lectivos,id'],
            'item_pagavel_id' => ['required', 'uuid', 'exists:item_pagaveis,id'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'data_vencimento' => ['required', 'date'],
        ]);

        $turma = Turma::with('inscricoesActivas.aluno')->findOrFail($data['turma_id']);
        $item = ItemPagavel::findOrFail($data['item_pagavel_id']);

        $criadas = 0;

        foreach ($turma->inscricoesActivas as $inscricao) {
            $existe = Propina::where([
                'aluno_id' => $inscricao->aluno_id,
                'ano_lectivo_id' => $data['ano_lectivo_id'],
                'item_pagavel_id' => $item->id,
                'mes' => $data['mes'],
            ])->exists();

            if ($existe) {
                continue;
            }

            Propina::create([
                'aluno_id' => $inscricao->aluno_id,
                'ano_lectivo_id' => $data['ano_lectivo_id'],
                'item_pagavel_id' => $item->id,
                'mes' => $data['mes'],
                'valor_devido' => $item->valor_padrao,
                'data_vencimento' => $data['data_vencimento'],
                'estado' => 'pendente',
            ]);

            $criadas++;
        }

        return back()->with('success', "{$criadas} propinas geradas com sucesso.");
    }

    public function edit(Propina $propina)
    {
        $this->authorize('update', $propina);

        return Inertia::render('tenant/Propina/Edit', [
            'propina' => $propina->load(['aluno:id,nome', 'itemPagavel:id,nome']),
        ]);
    }

    public function update(Request $request, Propina $propina)
    {
        $this->authorize('update', $propina);

        $data = $request->validate([
            'valor_devido' => ['required', 'numeric', 'min:0'],
            'data_vencimento' => ['required', 'date'],
            'estado' => ['required', 'in:pendente,pago,parcial,atrasado,isento'],
        ]);

        $propina->update($data);

        return redirect()->route('propinas.index')->with('success', 'Propina actualizada com sucesso.');
    }

    public function destroy(Propina $propina)
    {
        $this->authorize('delete', $propina);

        if ($propina->pagamentos()->exists()) {
            return back()->with('error', 'Não é possível apagar: já existem pagamentos registados para esta propina.');
        }

        $propina->delete();

        return redirect()->route('propinas.index')->with('success', 'Propina removida com sucesso.');
    }
}
