<?php

namespace App\Http\Controllers\Tenant\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClasseController extends Controller /* implements HasMiddleware */
{
    /*  public static function middleware(): array
    {
        return [
            new Middleware('permission:classes.index',  only: ['index']),
            new Middleware('permission:classes.show',   only: ['show']),
            new Middleware('permission:classes.create', only: ['store']),
            new Middleware('permission:classes.edit',   only: ['update']),
            new Middleware('permission:classes.delete', only: ['destroy']),
        ];
    } */

    public function index(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $classes = Classe::whereHas(
            'instituicaoCursos',
            fn ($q) => $q->where('instituicao_curso_id', $instituicaoCurso->id)
        )->get();

        return response()->json($classes);
    }

    public function store(Request $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $request->validate([
            'nome' => 'required|string|max:50|unique:classes,nome',
        ]);

        $classe = Classe::create([
            'nome' => $request->nome,
        ]);

        return response()->json($classe, 201);
    }

    public function show(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Classe $classe)
    {
        return response()->json($classe);
    }

    public function update(Request $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Classe $classe)
    {
        $request->validate([
            'nome' => 'required|string|max:50|unique:classes,nome,'.$classe->id,
        ]);

        $classe->update([
            'nome' => $request->nome,
        ]);

        return response()->json($classe);
    }

    public function destroy(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Classe $classe)
    {
        $classe->delete();

        return response()->json(['message' => 'Classe removida com sucesso.']);
    }
}
