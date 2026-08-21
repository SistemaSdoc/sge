<?php

namespace App\Http\Controllers\Tenant\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InstituicaoCurso\StoreDisciplinaRequest;
use App\Http\Requests\Tenant\InstituicaoCurso\UpdateDisciplinaRequest;
use App\Http\Resources\Tenant\DisciplinaResource;
use App\Models\Tenant\CursoClasseDisciplina;
use App\Models\Tenant\CursoDisciplina;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DisciplinaController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:disciplinas.index',  only: ['index']),
            new Middleware('permission:disciplinas.show',   only: ['show']),
            new Middleware('permission:disciplinas.create', only: ['store']),
            new Middleware('permission:disciplinas.edit',   only: ['update']),
            new Middleware('permission:disciplinas.delete', only: ['destroy']),
        ];
    }*/

    public function index(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $disciplinas = Disciplina::with(['classes' => function ($q) use ($instituicaoCurso) {
            $q->where('instituicao_curso_id', $instituicaoCurso->id);
        }, 'classes.classe'])
            ->whereHas('classes', function ($q) use ($instituicaoCurso) {
                $q->where('instituicao_curso_id', $instituicaoCurso->id);
            })
            ->get();

        return DisciplinaResource::collection($disciplinas);
    }

    public function store(StoreDisciplinaRequest $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $request->validate([
            'disciplina_id' => 'required|exists:disciplinas,id',
            'classes' => 'required|array',
            'classes.*' => 'required|exists:curso_instituicao_classe,id',
        ]);

        /* foreach ($request->classes as $classeId) {

            // evitar duplicação
            $jaExiste = CursoClasseDisciplina::where(
                'curso_instituicao_classe_id',
                $classeId
            )
                ->where('disciplina_id', $request->disciplina_id)
                ->exists();

            if (!$jaExiste) {
                CursoClasseDisciplina::create([
                    'curso_instituicao_classe_id' => $classeId,
                    'disciplina_id' => $request->disciplina_id,
                ]);
            }
        } */

        $disciplina = Disciplina::find($request->disciplina_id);

        return response()->json([
            'message' => 'Disciplina associada às classes com sucesso!',
            'data' => $disciplina,
        ]);
    }

    public function show(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Disciplina $disciplina)
    {
        return new DisciplinaResource($disciplina);
    }

    public function update(UpdateDisciplinaRequest $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Disciplina $disciplina)
    {
        $disciplina->update($request->validated());

        return new DisciplinaResource($disciplina);
    }

    public function destroy(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Disciplina $disciplina)
    {/*
        CursoDisciplina::where('curso_instituicao_id', $instituicaoCurso->id)
            ->where('disciplina_id', $disciplina->id)
            ->delete(); */

        return response()->json(['message' => 'Disciplina removida do curso com sucesso.']);
    }
}
