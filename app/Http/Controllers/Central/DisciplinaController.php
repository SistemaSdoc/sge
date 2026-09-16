<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\DisciplinaRequest;
use App\Models\Central\Disciplina;
use Inertia\Inertia;

class DisciplinaController extends Controller
{
    /**
     * Apresenta a lista de disciplinas, incluindo as arquivadas.
     */
    public function index()
    {
        return Inertia::render('central/disciplinas/index', [
            'disciplinas' => Disciplina::withTrashed()
                ->orderBy('nome')
                ->paginate(10),
        ]);
    }

    /**
     * Apresenta o formulário de criação de uma disciplina.
     */
    public function create()
    {
        return Inertia::render('central/disciplinas/create');
    }

    /**
     * Cria uma nova disciplina com os dados validados.
     */
    public function store(DisciplinaRequest $request)
    {
        Disciplina::query()->create($request->validated());

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina criada com sucesso.');
    }

    /**
     * Apresenta os dados de uma disciplina.
     */
    public function show(Disciplina $disciplina)
    {
        return Inertia::render('central/disciplinas/show', [
            'disciplina' => $disciplina,
        ]);
    }

    /**
     * Apresenta o formulário de edição de uma disciplina.
     */
    public function edit(Disciplina $disciplina)
    {
        return Inertia::render('central/disciplinas/edit', [
            'disciplina' => $disciplina,
        ]);
    }

    /**
     * Atualiza os dados de uma disciplina existente.
     */
    public function update(DisciplinaRequest $request, Disciplina $disciplina)
    {
        $disciplina->update($request->validated());

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina actualizada com sucesso.');
    }

    /**
     * Arquiva uma disciplina através de soft delete.
     */
    public function destroy(Disciplina $disciplina)
    {
        $disciplina->delete();

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina arquivada com sucesso.');
    }

    /**
     * Restaura uma disciplina previamente arquivada.
     */
    public function restore(string $disciplina)
    {
        Disciplina::withTrashed()->findOrFail($disciplina)->restore();

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina restaurada com sucesso.');
    }
}
