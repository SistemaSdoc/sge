<?php

namespace App\Http\Controllers;

use App\Http\Requests\Curso\CursoStoreRequest;
use App\Http\Requests\Curso\CursoUpdateRequest;
use App\Models\Curso;
use App\Models\InstituicaoCurso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursosController extends Controller
{
    public function index()
    {
        $cursos = Curso::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(1);

        return Inertia::render('cursos/index', [
            'cursos' => $cursos,
        ]);
    }

    public function create()
    {
        return Inertia::render('cursos/create');
    }

    public function store(CursoStoreRequest $request)
    {
        $request->validated();

        $curso = Curso::create([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
            'status' => 1,
        ]);

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Curso $curso)
    {
        return Inertia::render('cursos/show', [
            'curso' => $curso,
        ]);
    }

    public function edit(Curso $curso)
    {
        return Inertia::render('cursos/edit', [
            'curso' => $curso,
        ]);
    }

    public function update(CursoUpdateRequest $request, Curso $curso)
    {
        $request->validated();

        $curso->update([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
        ]);

        $curso->update($request->all());

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso atualizado com sucesso!',
        ]);
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso excluído com sucesso!',
        ]);
    }

    public function instituicoesTutoras(Curso $curso, Request $request)
    {
        $instituicaoId = $request->query('instituicao_id');

        $instituicoes = InstituicaoCurso::with('instituicao')
            ->where('curso_id', $curso->id)
            ->whereHas(
                'instituicao',
                fn ($q) => $q->where('tipo', 'instituto')
                    ->orWhere('id', $instituicaoId)
            )
            ->get()
            ->pluck('instituicao')
            ->unique('id')
            ->values()
            ->map(fn ($inst) => [
                'id' => $inst->id,
                'nome' => $inst->nome,
            ]);

        return response()->json(['data' => $instituicoes]);
    }
}