<?php

namespace App\Http\Controllers\Central;

use App\Actions\Central\Curso\ArchiveCurso;
use App\Actions\Central\Curso\CreateCurso;
use App\Actions\Central\Curso\RestoreCurso;
use App\Actions\Central\Curso\UpdateCurso;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Curso\StoreCursoRequest;
use App\Http\Requests\Central\Curso\UpdateCursoRequest;
use App\Models\Central\Curso;
use Inertia\Inertia;

class CursoController extends Controller
{
    public function __construct(
        private readonly CreateCurso $createCurso,
        private readonly UpdateCurso $updateCurso,
        private readonly ArchiveCurso $archiveCurso,
        private readonly RestoreCurso $restoreCurso,
    ) {
        $this->authorizeResource(Curso::class, 'curso');
    }

    public function index()
    {
        $cursos = Curso::withTrashed()
            ->orderBy('nome')
            ->paginate(10);

        return Inertia::render('central/cursos/index', [
            'cursos' => $cursos,
        ]);
    }

    public function create()
    {
        return Inertia::render('central/cursos/create');
    }

    public function store(StoreCursoRequest $request)
    {
        $this->createCurso->handle($request->validated());

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

    public function update(UpdateCursoRequest $request, Curso $curso)
    {
        $this->updateCurso->handle($curso, $request->validated());

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso actualizado com sucesso.');
    }

    public function destroy(Curso $curso)
    {
        $this->archiveCurso->handle($curso);

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso arquivado com sucesso.');
    }

    public function restore(Curso $curso)
    {
        $this->authorize('restore', $curso);

        $this->restoreCurso->handle($curso);

        return to_route('central.dashboard.cursos.index')
            ->with('success', 'Curso restaurado com sucesso.');
    }
}
