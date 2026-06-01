<?php

namespace App\Http\Controllers;

use App\Http\Requests\BancaPapRequest;
use App\Models\BancaJuriPap;
use App\Models\GrupoPap;
use App\Models\Professor;
use Illuminate\Http\Request;

class BancaJuriPapController extends Controller
{

    public function index($grupo_id)
    {
        $grupo = GrupoPap::with('jurados.professor')->findOrFail($grupo_id);

        return response()->json($grupo, $status = 200);
    }

    public function create($grupo_id)
    {
        $grupo = GrupoPap::with('turma.classeTurnoDisciplina')->findOrFail($grupo_id);

        $tdp = $grupo->turma->classeTurnoDisciplina->id;

        // Pega apenas professores do mesmo curso da turma
        $professores = Professor::whereHas('turnoDisciplinaProfessor', function ($q) use ($tdp) {
            $q->where('turno_disciplina_professor_id', $tdp);
        })->get();

        return response()->json(['grupo' => $grupo, 'professores' => $professores], $status = 200);

    }

    public function store(BancaPapRequest $request)
    {
        $request->validated();

        BancaJuriPap::where('grupo_pap_id', $request->grupo_pap_id)->delete(); // remove antigos

        $jurados = [
            ['professor_id' => $request->presidente, 'funcao' => 'Presidente da Banca'],
            ['professor_id' => $request->vogal1, 'funcao' => '1ºVogal'],
            ['professor_id' => $request->vogal2, 'funcao' => '2ºVogal'],
        ];

        foreach ($jurados as $juri) {
            BancaJuriPap::create([
                'grupo_pap_id' => $request->grupo_pap_id,
                'professor_id' => $juri['professor_id'],
                'funcao' => $juri['funcao'],
            ]);
        }
            return response()->json(null, $status = 201);
    }

    public function edit($grupo_id)
    {
        $grupo = GrupoPap::with('jurados.professor', 'turma.classeTurnoDisciplina')->findOrFail($grupo_id);

        $tdp = $grupo->turma->classeTurnoDisciplina->id;

        $professores = Professor::whereHas('turnoDisciplinaProfessor', function ($q) use ($tdp) {
            $q->where('turno_disciplina_professor_id', $tdp);
        })->get();

        return response()->json(['grupo' => $grupo, 'professores' => $professores], $status = 200);
    }

    public function update(Request $request, $grupo_id)
    {
        $request->validate([
            'presidente' => 'required|exists:professores,id',
            'vogal1' => 'required|exists:professores,id',
            'vogal2' => 'required|exists:professores,id',
        ]);

        BancaJuriPap::where('grupo_pap_id', $grupo_id)->delete();

        $jurados = [
            ['professor_id' => $request->presidente, 'funcao' => 'Presidente da Banca'],
            ['professor_id' => $request->vogal1, 'funcao' => '1ºVogal'],
            ['professor_id' => $request->vogal2, 'funcao' => '2ºVogal'],
        ];

        foreach ($jurados as $juri) {
            BancaJuriPap::create([
                'grupo_pap_id' => $grupo_id,
                'professor_id' => $juri['professor_id'],
                'funcao' => $juri['funcao'],
            ]);
        }

        return response()->json(null, $status = 200);
    }

    public function destroy(string $id)
    {
        //
    }
}
