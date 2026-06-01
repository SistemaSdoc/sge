<?php

namespace App\Http\Controllers;

use App\Http\Requests\CursoRequest;
use App\Http\Resources\Curso\CursoResourceIndex;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\InstituicaoCurso;
use Illuminate\Http\Request;

class CursosController extends Controller
{
    public function index()
    {
        $cursos = Curso::orderBy('created_at', 'desc')->get();

        return CursoResourceIndex::collection($cursos);
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

        return response()->json($curso, 201);
    }

    public function show(Curso $curso)
    {
        return response()->json($curso);
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
        $curso->classes()->sync($request->classes);

        return response()->json(status: 200);
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
                'id'   => $inst->id,
                'nome' => $inst->nome,
            ]);

        return response()->json(['data' => $instituicoes]);
    }
}
