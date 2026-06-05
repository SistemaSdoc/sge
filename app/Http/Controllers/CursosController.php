<?php

namespace App\Http\Controllers;

use App\Http\Requests\CursoRequest;
use App\Http\Resources\Curso\CursoResourceIndex;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\InstituicaoCurso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursosController extends Controller
{
    public function index()
    {
        $cursos = Curso::all();

        return Inertia::render('cursos/index', [
            'cursos' => $cursos,
        ]);
    }

    public function create()
    {

        return Inertia::render('cursos/create');
    }

    public function store(CursoRequest $request)
    {
        // Validação
        $request->validated();

        // Criar curso
        $curso = Curso::create([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
            'status' => 1,
        ]);

        /* // salvar relação N:N
        if ($request->has('instituicoes')) {
            $curso->instituicoes()->attach($request->instituicoes);
        } */

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Curso $curso)
    {
        return response()->json($curso);
    }

    public function edit(Curso $curso)
    {

        return Inertia::render('cursos/edit', [
            'curso' => $curso
        ]);
    }

    public function update(CursoRequest $request, Curso $curso)
    {
        $request->validated();

        $curso->update([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
        ]);

        $curso->update($request->all());

        //sincroniza pivot
        //$curso->classes()->sync($request->classes);

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso atualizado com sucesso!',
        ]);
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();

        return response(status: 204);
    }

    public function instituicoesTutoras(Curso $curso, Request $request)
    {
        $instituicaoId = $request->query('instituicao_id');

        $instituicoes = InstituicaoCurso::with('instituicao')
            ->where('curso_id', $curso->id)
            ->whereHas(
                'instituicao',
                fn($q) =>
                $q->where('tipo', 'instituto')
                    ->orWhere('id', $instituicaoId)
            )
            ->get()
            ->pluck('instituicao')
            ->unique('id')
            ->values()
            ->map(fn($inst) => [
                'id' => $inst->id,
                'nome' => $inst->nome,
            ]);

        return response()->json(['data' => $instituicoes]);
    }
}
