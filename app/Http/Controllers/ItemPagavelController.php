<?php

namespace App\Http\Controllers;

use App\Models\ItemPagavel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemPagavelController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ItemPagavel::class);

        $itens = ItemPagavel::query()
            ->where('instituicao_id', $request->user()->instituicao_id)
            ->orderBy('nome')
            ->paginate(15)
            ->through(fn(ItemPagavel $item) => [
                'id' => $item->id,
                'nome' => $item->nome,
                'tipo' => $item->tipo,
                'valor_padrao' => $item->valor_padrao,
                'can' => [
                    'update' => $request->user()->can('update', $item),
                    'delete' => $request->user()->can('delete', $item),
                ],
            ]);

        return Inertia::render('ItemPagavel/Index', [
            'itens' => $itens,
            'can' => [
                'create' => $request->user()->can('create', ItemPagavel::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', ItemPagavel::class);

        return Inertia::render('ItemPagavel/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ItemPagavel::class);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:mensalidade,matricula,taxa,outro'],
            'valor_padrao' => ['required', 'numeric', 'min:0'],
        ]);

        $data['instituicao_id'] = $request->user()->instituicao_id;

        ItemPagavel::create($data);

        return redirect()->route('item-pagaveis.index')->with('success', 'Item pagável criado com sucesso.');
    }

    public function edit(ItemPagavel $itemPagavel)
    {
        $this->authorize('update', $itemPagavel);

        return Inertia::render('ItemPagavel/Edit', [
            'itemPagavel' => $itemPagavel,
        ]);
    }

    public function update(Request $request, ItemPagavel $itemPagavel)
    {
        $this->authorize('update', $itemPagavel);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:mensalidade,matricula,taxa,outro'],
            'valor_padrao' => ['required', 'numeric', 'min:0'],
        ]);

        $itemPagavel->update($data);

        return redirect()->route('item-pagaveis.index')->with('success', 'Item pagável actualizado com sucesso.');
    }

    public function destroy(ItemPagavel $itemPagavel)
    {
        $this->authorize('delete', $itemPagavel);

        if ($itemPagavel->propinas()->exists()) {
            return back()->with('error', 'Não é possível apagar: existem propinas associadas a este item.');
        }

        $itemPagavel->delete();

        return redirect()->route('item-pagaveis.index')->with('success', 'Item pagável removido com sucesso.');
    }
}