<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CursoRequest;
use App\Models\Central\Curso;
use Inertia\Inertia;

class CursoController extends Controller
{
    public function index()
    {
        return Inertia::render('central/cursos/index', [
            'cursos' => Curso::withTrashed()
                ->orderBy('nome')
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('central/cursos/create');
    }

    public function store(CursoRequest $request)
    {
        Curso::query()->create($request->validated());

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso criado com sucesso.');
    }

    public function show(Curso $curso)
    {
        return Inertia::render('central/cursos/show', [
            'curso' => $curso,
        ]);
    }

    public function edit(Curso $curso)
    {
        return Inertia::render('central/cursos/edit', [
            'curso' => $curso,
        ]);
    }

    public function update(CursoRequest $request, Curso $curso)
    {
        $curso->update($request->validated());

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso actualizado com sucesso.');
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso arquivado com sucesso.');
    }

    public function restore(string $curso)
    {
        Curso::withTrashed()->findOrFail($curso)->restore();

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso restaurado com sucesso.');
    }
}
