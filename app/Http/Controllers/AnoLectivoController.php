<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnoLectivoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AnoLectivo::class);

        $anosLectivos = AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->paginate(15)
            ->through(fn(AnoLectivo $ano) => [
                'id' => $ano->id,
                'nome' => $ano->nome,
                'data_inicio' => $ano->data_inicio->format('Y-m-d'),
                'data_fim' => $ano->data_fim->format('Y-m-d'),
                'activo' => $ano->activo,
                'can' => [
                    'update' => $request->user()->can('update', $ano),
                    'delete' => $request->user()->can('delete', $ano),
                ],
            ]);

        return Inertia::render('AnoLectivo/Index', [
            'anosLectivos' => $anosLectivos,
            'can' => [
                'create' => $request->user()->can('create', AnoLectivo::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', AnoLectivo::class);

        return Inertia::render('AnoLectivo/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AnoLectivo::class);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:20', 'unique:ano_lectivos,nome'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after:data_inicio'],
            'activo' => ['boolean'],
        ]);

        if ($data['activo'] ?? false) {
            AnoLectivo::where('activo', true)->update(['activo' => false]);
        }

        AnoLectivo::create($data);

        return redirect()->route('ano-lectivos.index')->with('success', 'Ano lectivo criado com sucesso.');
    }

    public function edit(AnoLectivo $anoLectivo)
    {
        $this->authorize('update', $anoLectivo);

        return Inertia::render('AnoLectivo/Edit', [
            'anoLectivo' => $anoLectivo,
        ]);
    }

    public function update(Request $request, AnoLectivo $anoLectivo)
    {
        $this->authorize('update', $anoLectivo);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:20', 'unique:ano_lectivos,nome,' . $anoLectivo->id],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after:data_inicio'],
            'activo' => ['boolean'],
        ]);

        if ($data['activo'] ?? false) {
            AnoLectivo::where('id', '!=', $anoLectivo->id)->where('activo', true)->update(['activo' => false]);
        }

        $anoLectivo->update($data);

        return redirect()->route('ano-lectivos.index')->with('success', 'Ano lectivo actualizado com sucesso.');
    }

    public function destroy(AnoLectivo $anoLectivo)
    {
        $this->authorize('delete', $anoLectivo);

        if ($anoLectivo->propinas()->exists()) {
            return back()->with('error', 'Não é possível apagar: existem propinas associadas a este ano lectivo.');
        }

        $anoLectivo->delete();

        return redirect()->route('ano-lectivos.index')->with('success', 'Ano lectivo removido com sucesso.');
    }
}