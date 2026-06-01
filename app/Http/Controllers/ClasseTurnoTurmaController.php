<?php

namespace App\Http\Controllers;

use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClasseTurnoTurmaController extends Controller /* implements HasMiddleware */
{
    /* public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index',  only: ['index']),
            new Middleware('permission:turmas.create', only: ['store']),
            new Middleware('permission:turmas.edit',   only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy']),
        ];
    } */

    /**
     * Display a listing of the resource.
     */
    public function index(CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $turmas = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)->get();

        return response()->json(
            $turmas->map(fn($turma) => [
                'id'         => $turma->id,
                'nome'       => $turma->nome,
                'max_alunos' => $turma->max_alunos,
                'alunos_count' => $turma->alunos()->count(),
            ])
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $request->validate([
            'nome'       => 'required|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        $jaExiste = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->where('nome', $request->nome)
            ->exists();

        if ($jaExiste) {
            return response()->json(['message' => 'Já existe uma turma com este nome neste turno.'], 422);
        }

        Turma::create([
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'nome'                  => $request->nome,
            'max_alunos'            => $request->max_alunos,
        ]);

        return response()->json(status: 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        $request->validate([
            'nome'       => 'sometimes|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        $turma->update($request->only(['nome', 'max_alunos']));

        return response()->json(status: 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        $temAlunos = $turma->alunos()->exists();

        if ($temAlunos) {
            return response()->json([
                'message' => 'Não é possível remover uma turma que tem alunos associados.'
            ], 422);
        }

        $turma->delete();

        return response()->json(status: 200);
    }
}
