<?php

namespace App\Http\Controllers\Central;

use App\Http\Requests\DisciplinaRequest;
use App\Models\Central\Disciplina;
use Illuminate\Http\Request;

class DisciplinaController extends Controller
{
    public function index()
    {
        $disciplinas = Disciplina::paginate(10);

        return response()->json($disciplinas);
    }

    public function create()
    {
        return view('disciplinas.create');
    }

    public function store(DisciplinaRequest $request)
    {
        // Aqui já está validado automaticamente pelo DisciplinaRequest

        $disciplina = Disciplina::create($request->all());

        return redirect()->route('disciplinas.index')
            ->with('success', 'Disciplina criada com sucesso!');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Disciplina $disciplina)
    {
        return view('disciplinas.edit', compact('disciplina'));
    }

    public function update(Request $request, Disciplina $disciplina)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'carga_horaria' => 'required|integer|min:1',
        ]);

        // Atualiza os dados da disciplina
        $disciplina->update($request->all());

        return redirect()->route('disciplinas.index')
            ->with('success', 'Disciplina atualizada com sucesso!');
    }

    public function destroy(Disciplina $disciplina)
    {
        $disciplina->delete();

        return redirect()->route('disciplinas.index')
            ->with('success', 'Disciplina excluída com sucesso!');
    }
}
