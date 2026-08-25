<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ItemPagavel\StoreItemPagavelRequest;
use App\Http\Requests\Tenant\ItemPagavel\UpdateItemPagavelRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\ItemPagavel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ItemPagavelController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', ItemPagavel::class);

        Log::info('ItemPagavelController@index - início', [
            'user_id' => $request->user()->id,
            'instituicao_id' => $request->user()->instituicao_id,
            'query_params' => $request->query(),
        ]);

        $itens = ItemPagavel::query()
            ->where('instituicao_id', $request->user()->instituicao_id)
            ->with('cursoClasse.classe:id,nome') // <- carrega também a classe para obter o nome real
            ->orderBy('nome')
            ->paginate(15);

        Log::info('ItemPagavelController@index - resultado da query', [
            'total' => $itens->total(),
            'per_page' => $itens->perPage(),
            'current_page' => $itens->currentPage(),
            'ids_pagina_atual' => $itens->pluck('id'),
        ]);

        $itens = $itens->through(function (ItemPagavel $item) use ($request) {
            $dados = [
                'id' => $item->id,
                'nome' => $item->nome,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'frequencia' => $item->frequencia,
                'multa_dias_tolerancia' => $item->multa_dias_tolerancia,
                'multa_valor' => $item->multa_valor,
                'ativo' => $item->ativo,
                'curso_classe' => $item->cursoClasse?->classe?->nome, // <- nome vem de classe, não de cursoClasse
                'can' => [
                    'update' => $request->user()->can('update', $item) ?? true,
                    'delete' => $request->user()->can('delete', $item) ?? true,
                ],
            ];

            Log::debug('ItemPagavelController@index - item transformado', $dados);

            return $dados;
        });

        Log::info('ItemPagavelController@index - a renderizar Inertia', [
            'can_create' => $request->user()->can('create', ItemPagavel::class) ?? true,
        ]);

        return Inertia::render('tenant/itens-pagaveis/index', [
            'itens' => $itens,
            'can' => [
                'create' => $request->user()->can('create', ItemPagavel::class) ?? true,
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', ItemPagavel::class);

        $instituicao = Auth::guard('tenant')->user()->instituicao_id; // ← Pega da autenticação

        return Inertia::render('tenant/itens-pagaveis/create', [
            'cursosClasse' => CursoClasse::query()
                ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
                ->whereHas('cursoTutelado.instituicaoCurso', function ($q) use ($instituicao) {
                    $q->where('instituicao_id', $instituicao); // ← Filtra
                })
                ->get()
                ->map(fn(CursoClasse $cc) => [
                    'id' => $cc->id,
                    'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome . ' — ' . $cc->classe->nome,
                ]),
        ]);
    }

    public function store(StoreItemPagavelRequest $request)
    {
        $item = ItemPagavel::create([
            ...$request->validated(),
            'instituicao_id' => $request->user()->instituicao_id,
        ]);
        
        if ($item->tipo === 'documento' && $request->filled('subtipo')) {
            \App\Models\Documento::create([
                'item_pagavel_id' => $item->id,
                'instituicao_id' => $request->user()->instituicao_id,
                'subtipo' => $request->input('subtipo'),
            ]);
        }

                return redirect()->route('tenant.dashboard.itens-pagaveis.index')->with('success', 'Item pagável criado com sucesso.');

    }
    public function edit(ItemPagavel $itemPagavel)
    {
        $itemPagavel->load('documento');

        $cursosClasse = CursoClasse::query()
            ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
            ->get()
            ->map(fn(CursoClasse $cc) => [
                'id' => $cc->id,
                'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome . ' — ' . $cc->classe->nome,
            ]);

        Log::debug('[ItemPagavelController@edit] cursosClasse carregados', [
            'total' => $cursosClasse->count(),
            'cursosClasse' => $cursosClasse->toArray(),
        ]);

        return Inertia::render('tenant/itens-pagaveis/edit', [
            'itemPagavel' => [
                'id' => $itemPagavel->id,
                'nome' => $itemPagavel->nome,
                'tipo' => $itemPagavel->tipo,
                'subtipo' => $itemPagavel->documento?->subtipo,
                'descricao' => $itemPagavel->descricao,
                'valor' => $itemPagavel->valor,
                'frequencia' => $itemPagavel->frequencia,
                'curso_classe_id' => $itemPagavel->curso_classe_id !== null
                    ? (string) $itemPagavel->curso_classe_id
                    : null,
                'multa_dias_tolerancia' => $itemPagavel->multa_dias_tolerancia,
                'multa_valor' => $itemPagavel->multa_valor,
                'ativo' => (bool) $itemPagavel->ativo,
            ],
            'cursosClasse' => $cursosClasse,
        ]);
    }

    public function update(UpdateItemPagavelRequest $request, ItemPagavel $itemPagavel)
    {
        $itemPagavel->update($request->validated());

        if ($itemPagavel->tipo === 'documento' && $request->filled('subtipo')) {
            $itemPagavel->documento()->updateOrCreate(
                ['item_pagavel_id' => $itemPagavel->id],
                [
                    'instituicao_id' => $request->user()->instituicao_id,
                    'subtipo' => $request->input('subtipo'),
                ]
            );
        }

        return redirect()->route('tenant.dashboard.itens-pagaveis.index')->with('success', 'Item actualizado com sucesso.');
    }

    public function destroy(ItemPagavel $itemPagavel)
    {
        // $this->authorize('delete', $itemPagavel);

        if ($itemPagavel->propinas()->exists()) {
            return back()->with('error', 'Não é possível apagar: existem propinas associadas a este item.');
        }

        $itemPagavel->delete();

        return redirect()->route('tenant.dashboard.itens-pagaveis.index')->with('success', 'Item pagável removido com sucesso.');
    }
}
