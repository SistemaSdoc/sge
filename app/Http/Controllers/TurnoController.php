<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Turno::class, 'turno', [
            'except' => ['create'],
        ]);
    }

    public function index()
    {
        $turnos = Turno::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($turno) {
                return [
                    'id' => $turno->id,
                    'nome' => $turno->nome,
                    'can' => [
                        'view_turno' => Auth::user()->can('view', $turno),
                        'edit_turno' => Auth::user()->can('update', $turno),
                        'delete_turno' => Auth::user()->can('delete', $turno),
                    ],
                ];
            });

        return Inertia::render('turnos/index', [
            'turnos' => $turnos,
            'can' => [
                'create_turno' => Auth::user()->can('create', Turno::class),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('turnos/create', [
            'can' => [
                'create_turno' => Auth::user()->can('create', Turno::class),
            ],
        ]);
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

    public function show(Turno $turno)
    {
        return Inertia::render('turnos/show', [
            'turno' => $turno,
            'can' => [
                'view_turno' => Auth::user()->can('view', $turno),
                'edit_turno' => Auth::user()->can('update', $turno),
                'delete_turno' => Auth::user()->can('delete', $turno),
            ],
        ]);
    }

    public function edit(Turno $turno)
    {
        return Inertia::render('turnos/edit', [
            'turno' => $turno,
            'can' => [
                'edit_turno' => Auth::user()->can('update', $turno),
            ],
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
