<?php

namespace App\Http\Controllers\NaoUsados;

use App\Http\Controllers\Controller;
use App\Models\CursoClasseTurno;
use App\Models\Professor;
use App\Models\Turma;
use App\Models\TurmaProfessor;
use Illuminate\Http\Request;

class TurmaProfessorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $turmaProfessores = TurmaProfessor::with([
            'turma',
            'professor',
            // ALTERADO: Acesso via cursoClasseTurno
            'turma.cursoClasseTurno.classeTurnoDisciplina.disciplina',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
        ])->get();

        return view('turma_professor.index', compact('turmaProfessores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $turmas = Turma::with([
            // ALTERADO: Relação cursoClasseTurno
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            // ADICIONADO: Disciplinas do turno
            'cursoClasseTurno.classeTurnoDisciplina.disciplina',
        ])->get();

        $professores = Professor::with('user')->get();

        // REMOVIDO: $classeTurnoDisciplinas não é mais necessário
        // Agora as disciplinas vêm via cursoClasseTurno

        return view('turma_professor.create', compact(
            'turmas',
            'professores'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'professor_id' => 'required|exists:professores,id',
        ]);

        // impedir duplicação por turma + professor
        $existe = TurmaProfessor::where('turma_id', $request->turma_id)
            ->where('professor_id', $request->professor_id)
            ->exists();

        if ($existe) {
            return redirect()->back()
                ->with('error', 'Este professor já está atribuído nesta turma!');
        }

        TurmaProfessor::create([
            'turma_id' => $request->turma_id,
            'professor_id' => $request->professor_id,
        ]);

        return redirect()->route('turma_professor.index')
            ->with('success', 'Atribuição criada com sucesso!');
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
    public function edit(TurmaProfessor $turmaProfessor)
    {
        $turmas = Turma::all();
        $professores = Professor::all();

        return view('turma_professores.edit', compact('turmaProfessor', 'turmas', 'professores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TurmaProfessor $turmaProfessor)
    {
        $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'professor_id' => 'required|exists:professores,id',
        ]);

        $turmaProfessor->update([
            'turma_id' => $request->turma_id,
            'professor_id' => $request->professor_id,
        ]);

        return redirect()->route('turma_professor.index')->with('success', 'Atribuição atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TurmaProfessor $turmaProfessor)
    {
        $turmaProfessor->delete();

        return redirect()->route('turma_professor.index')->with('success', 'Atribuição excluída com sucesso!');
    }
}
