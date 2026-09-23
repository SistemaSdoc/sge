<?php

namespace App\Http\Controllers\Central;

use App\Actions\Central\Disciplina\ArchiveDisciplina;
use App\Actions\Central\Disciplina\CreateDisciplina;
use App\Actions\Central\Disciplina\RestoreDisciplina;
use App\Actions\Central\Disciplina\UpdateDisciplina;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Disciplina\StoreDisciplinaRequest;
use App\Http\Requests\Central\Disciplina\UpdateDisciplinaRequest;
use App\Models\Central\Disciplina;
use Inertia\Inertia;

class DisciplinaController extends Controller
{
    public function __construct(
        private readonly CreateDisciplina $createDisciplina,
        private readonly UpdateDisciplina $updateDisciplina,
        private readonly ArchiveDisciplina $archiveDisciplina,
        private readonly RestoreDisciplina $restoreDisciplina,
    ) {
        $this->authorizeResource(Disciplina::class, 'disciplina');
    }

    /**
     * Apresenta a lista de disciplinas, incluindo as arquivadas.
     */
    public function index()
    {
        return Inertia::render('central/disciplinas/index', [
            'disciplinas' => Disciplina::withTrashed()
                ->orderBy('created_at', 'desc')
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
    public function store(StoreDisciplinaRequest $request)
    {
        $this->createDisciplina->handle($request->validated());

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
    public function update(
        UpdateDisciplinaRequest $request, Disciplina $disciplina
    ) {
        $this->updateDisciplina->handle($disciplina, $request->validated());

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina actualizada com sucesso.');
    }

    /**
     * Arquiva uma disciplina através de soft delete.
     */
    public function destroy(Disciplina $disciplina)
    {
        $this->archiveDisciplina->handle($disciplina);

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina arquivada com sucesso.');
    }

    /**
     * Restaura uma disciplina previamente arquivada.
     */
    public function restore(Disciplina $disciplina)
    {
        $this->authorize('restore', $disciplina);
        $this->restoreDisciplina->handle($disciplina);

        return to_route('central.dashboard.disciplinas.index')
            ->with('success', 'Disciplina restaurada com sucesso.');
    }
}
