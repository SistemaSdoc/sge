<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Turno::class);

        $turnos = Turno::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10);

        return Inertia('turnos/index', [
            'turnos' => $turnos,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Turno::class);

        return Inertia('turnos/create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Turno::class);
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

    public function show(Turno $turno)
    {
        //$this->authorize('view', $turno);

        return Inertia('turnos/show', [
            'turno' => $turno,
        ]);
    }

    public function edit(Turno $turno)
    {
        $this->authorize('update', $turno);

        return Inertia('turnos/edit', [
            'turno' => $turno,
        ]);
    }

    public function update(Request $request, Turno $turno)
    {
        $this->authorize('update', $turno);

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
        $this->authorize('delete', $turno);
        
        $turno->delete();

        return to_route('turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno excluído com sucesso!',
        ]);
    }
}
