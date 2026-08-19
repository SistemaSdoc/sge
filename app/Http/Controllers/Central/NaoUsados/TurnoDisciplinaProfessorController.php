<?php

namespace App\Http\Controllers\NaoUsados;

use App\Models\TurnoDisciplinaProfessor;
use Illuminate\Http\Request;

class TurnoDisciplinaProfessorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dados = TurnoDisciplinaProfessor::with([
            'professor.user',
            'classeTurnoDisciplina.cursoClasseTurno.cursoClasse.curso',
            'classeTurnoDisciplina.cursoClasseTurno.turno',
            'classeTurnoDisciplina.disciplina',
        ])->get();

        return response()->json($dados);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'professor_id' => 'required|exists:professores,id',
            'classe_turno_disciplina_id' => 'required|exists:classe_turno_disciplina,id',
        ]);

        // Evitar duplicação
        $existe = TurnoDisciplinaProfessor::where('professor_id', $request->professor_id)
            ->where('classe_turno_disciplina_id', $request->classe_turno_disciplina_id)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Professor já está atribuído a esta disciplina/turno.',
            ], 400);
        }

        $vinculo = TurnoDisciplinaProfessor::create([
            'professor_id' => $request->professor_id,
            'classe_turno_disciplina_id' => $request->classe_turno_disciplina_id,
        ]);

        return response()->json([
            'message' => 'Professor atribuído com sucesso!',
            'data' => $vinculo,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vinculo = TurnoDisciplinaProfessor::findOrFail($id);
        $vinculo->delete();

        return response()->json([
            'message' => 'Vínculo removido com sucesso!',
        ]);
    }
}
