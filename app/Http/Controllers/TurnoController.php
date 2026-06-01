<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function index()
    {
        // Carrega turnos (pode ser filtrado por instituição se tiver relação)
        $turnos = Turno::all();

        return response()->json($turnos, status: 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:50',
        ]);

        Turno::create([
            'nome' => $request->nome,
        ]);

        return response()->json(status: 201);
    }

    public function show(string $id)
    {
        $turno = Turno::findOrFail($id);

        return response()->json($turno, status: 200);
    }

    public function edit(Turno $turno)
    {
        return response()->json($turno, status: 200);
    }

    public function update(Request $request, Turno $turno)
    {
        $turno->update([
            'nome' => $request->nome,
        ]);

        return response()->json(status: 200);
    }

    public function destroy(Turno $turno)
    {
        $turno->delete();

        return response()->json(status: 200);
    }
}
