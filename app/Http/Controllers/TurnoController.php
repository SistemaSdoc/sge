<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function index()
    {
        // Carrega turnos (pode ser filtrado por instituição se tiver relação)
        $turnos = Turno::all();

        return Inertia('turnos/index', [
            'turnos' => $turnos,
        ]);
    }

    public function create()
    {
        return Inertia('turnos/create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:50',
        ]);

        Turno::create([
            'nome' => $request->nome,
        ]);

        return to_route('turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno criado com sucesso!',
        ]);
    }

    public function show(string $id)
    {
        $turno = Turno::findOrFail($id);

        return Inertia('turnos/show', [
            'turno' => $turno,
        ]);
    }

    public function edit(Turno $turno)
    {
        return Inertia('turnos/edit', [
            'turno' => $turno,
        ]);
    }

    public function update(Request $request, Turno $turno)
    {
        $turno->update([
            'nome' => $request->nome,
        ]);

        return to_route('turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno atualizado com sucesso!',
        ]);
    }

    public function destroy(Turno $turno)
    {
        $turno->delete();

        return to_route('turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno excluído com sucesso!',
        ]);
    }
}
