<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemPagavel\StoreItemPagavelRequest;
use App\Http\Requests\ItemPagavel\UpdateItemPagavelRequest;
use App\Models\CursoClasse;
use App\Models\ItemPagavel;
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

        // DEBUG: confirmar se o problema é a coluna 'nome' inexistente em CursoClasse
        // ou se é mesmo a relação a não resolver
        $itens->getCollection()->each(function (ItemPagavel $item) {
            Log::debug('ItemPagavelController@index - DEBUG curso_classe', [
                'item_id' => $item->id,
                'item_nome' => $item->nome,
                'curso_classe_id_bruto' => $item->getAttributes()['curso_classe_id'] ?? null,
                'cursoClasse_carregada' => $item->relationLoaded('cursoClasse'),
                'cursoClasse_atributos' => $item->cursoClasse?->getAttributes(),
                'cursoClasse_tem_nome' => $item->cursoClasse ? array_key_exists('nome', $item->cursoClasse->getAttributes()) : null,
                'classe_relacionada' => $item->cursoClasse?->classe,
                'nome_correto_via_classe' => $item->cursoClasse?->classe?->nome,
            ]);
        });

        $itens = $itens->through(function (ItemPagavel $item) use ($request) {
            $dados = [
                'id' => $item->id,
                'nome' => $item->nome,
                'tipo' => $item->tipo,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'frequencia' => $item->frequencia,
                'ativo' => $item->ativo,
                'curso_classe' => $item->cursoClasse?->classe?->nome, // <- corrigido: nome vem de classe, não de cursoClasse
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

        $instituicao = Auth::user()->instituicao_id; // ← Pega da autenticação

        return Inertia::render('itens-pagaveis/create', [
            'cursosClasse' => CursoClasse::query()
                ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
                ->whereHas('cursoTutelado.instituicaoCurso', function ($q) use ($instituicao) {
                    $q->where('instituicao_id', $instituicao); // ← Filtra
                })
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
        Log::debug('[ItemPagavelController@edit] INICIO', [
            'item_id' => $itemPagavel->id,
            'item_atributos' => $itemPagavel->getAttributes(),
        ]);

        $cursosClasse = CursoClasse::query()
            ->with(['classe:id,nome', 'cursoTutelado.instituicaoCurso.curso:id,nome'])
            ->get()
            ->map(fn (CursoClasse $cc) => [
                'id' => $cc->id,
                'nome' => $cc->cursoTutelado->instituicaoCurso->curso->nome.' — '.$cc->classe->nome,
            ]);

        Log::debug('[ItemPagavelController@edit] cursosClasse carregados', [
            'total' => $cursosClasse->count(),
            'cursosClasse' => $cursosClasse->toArray(),
        ]);

        $props = [
            'itemPagavel' => $itemPagavel,
            'cursosClasse' => $cursosClasse,
        ];

        Log::debug('[ItemPagavelController@edit] props enviadas ao Inertia::render', $props);

        return Inertia::render('itens-pagaveis/edit', [
            'itemPagavel' => [
                'id' => $itemPagavel->id,
                'nome' => $itemPagavel->nome,
                'descricao' => $itemPagavel->descricao,
                'valor' => $itemPagavel->valor,
                'frequencia' => $itemPagavel->frequencia,
                'curso_classe_id' => $itemPagavel->curso_classe_id !== null
                    ? (string) $itemPagavel->curso_classe_id
                    : null,
                'ativo' => (bool) $itemPagavel->ativo,
            ],
            'cursosClasse' => $cursosClasse, // já mapeado com id e nome
        ]);
    }

    public function update(UpdateItemPagavelRequest $request, ItemPagavel $itemPagavel)
    {
        Log::debug('[ItemPagavelController@update] INICIO', [
            'item_id' => $itemPagavel->id,
            'item_antes' => $itemPagavel->getAttributes(),
            'request_all' => $request->all(),
        ]);

        $validado = $request->validated();

        Log::debug('[ItemPagavelController@update] dados validados (validated())', [
            'item_id' => $itemPagavel->id,
            'validado' => $validado,
        ]);

        if (empty($validado)) {
            Log::warning('[ItemPagavelController@update] validated() veio VAZIO — provavelmente rules() da FormRequest não batem com os campos enviados', [
                'item_id' => $itemPagavel->id,
                'campos_recebidos' => array_keys($request->all()),
            ]);
        }

        $resultado = $itemPagavel->update($validado);

        Log::debug('[ItemPagavelController@update] resultado do update()', [
            'item_id' => $itemPagavel->id,
            'update_retornou' => $resultado,
            'wasChanged' => $itemPagavel->wasChanged(),
            'changes' => $itemPagavel->getChanges(),
            'item_depois' => $itemPagavel->fresh()?->getAttributes(),
        ]);

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
