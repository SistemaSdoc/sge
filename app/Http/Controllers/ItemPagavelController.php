<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemPagavel\StoreItemPagavelRequest;
use App\Http\Requests\ItemPagavel\UpdateItemPagavelRequest;
use App\Models\CursoClasse;
use App\Models\ItemPagavel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemPagavelController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', ItemPagavel::class);

        $itens = ItemPagavel::query()
            ->where('instituicao_id', $request->user()->instituicao_id)
           ->with('cursoClasse:id,classe_id,curso_tutelado_id,nivel_ensino_id')
            ->orderBy('nome')
            ->paginate(15)
            ->through(fn (ItemPagavel $item) => [
                'id' => $item->id,
                'nome' => $item->nome,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'frequencia' => $item->frequencia,
                'ativo' => $item->ativo,
                'curso_classe' => $item->cursoClasse?->nome,
                'can' => [
                    'update' => $request->user()->can('update', $item) ?? true,
                    'delete' => $request->user()->can('delete', $item) ?? true,
                ],
            ]);

        return Inertia::render('itens-pagaveis/index', [
            'itens' => $itens,
            'can' => [
                'create' => $request->user()->can('create', ItemPagavel::class) ?? true,
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', ItemPagavel::class);

        return Inertia::render('itens-pagaveis/create', [
            'cursosClasse' => CursoClasse::query()
                ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
                ->get()
                ->map(fn (CursoClasse $cc) => [
                    'id' => $cc->id,
                    'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome.' — '.$cc->classe->nome,
                ]),
        ]);
    }

    public function store(StoreItemPagavelRequest $request)
    {
        ItemPagavel::create([
            ...$request->validated(),
            'instituicao_id' => $request->user()->instituicao_id,
        ]);

        return redirect()->route('itens-pagaveis.index')->with('success', 'Item pagável criado com sucesso.');
    }

    public function edit(ItemPagavel $itemPagavel)
    {
        $this->authorize('update', $itemPagavel);

        return Inertia::render('itens-pagaveis/edit', [
            'itemPagavel' => $itemPagavel,
            'cursosClasse' => CursoClasse::query()
                ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
                ->get()
                ->map(fn (CursoClasse $cc) => [
                    'id' => $cc->id,
                    'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome.' — '.$cc->classe->nome,
                ]),
        ]);
    }

    public function update(UpdateItemPagavelRequest $request, ItemPagavel $itemPagavel)
    {
        $itemPagavel->update($request->validated());

        return redirect()->route('itens-pagaveis.index')->with('success', 'Item pagável actualizado com sucesso.');
    }

    public function destroy(ItemPagavel $itemPagavel)
    {
        // $this->authorize('delete', $itemPagavel);

        if ($itemPagavel->propinas()->exists()) {
            return back()->with('error', 'Não é possível apagar: existem propinas associadas a este item.');
        }

        $itemPagavel->delete();

        return redirect()->route('itens-pagaveis.index')->with('success', 'Item pagável removido com sucesso.');
    }
}
