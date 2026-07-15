<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnoLectivoController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', AnoLectivo::class);

        $anosLectivos = AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->paginate(15)
            ->through(fn (AnoLectivo $ano) => [
                'id' => $ano->id,
                'nome' => $ano->nome,           // accessor
                'data_inicio' => $ano->data_inicio->format('Y-m-d'),
                'data_fim' => $ano->data_fim->format('Y-m-d'),
                'estado' => $ano->estado,       // accessor: planeado | a_decorrer | encerrado
                'can' => [
                    'update' => $request->user()->can('update', $ano) ?? true,
                    'delete' => $request->user()->can('delete', $ano) ?? true,
                ],
            ]);

        return Inertia::render('anos-lectivos/index', [
            'anosLectivos' => $anosLectivos,
            'can' => [
                'create' => $request->user()->can('create', AnoLectivo::class),
            ],
        ]);
    }

    public function create()
    {
        // $this->authorize('create', AnoLectivo::class);

        return Inertia::render('anos-lectivos/create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after:data_inicio'],
        ]);

        $sobrepoe = AnoLectivo::where('data_inicio', '<=', $data['data_fim'])
            ->where('data_fim', '>=', $data['data_inicio'])
            ->exists();

        if ($sobrepoe) {
            return back()
                ->withErrors(['data_inicio' => 'Já existe um ano lectivo que se sobrepõe a este período.'])
                ->withInput();
        }

        AnoLectivo::create($data);

        return redirect()->route('anos-lectivos.index')->with('success', 'Ano lectivo criado com sucesso.');
    }

    public function edit(AnoLectivo $anoLectivo)
    {
        $this->authorize('update', $anoLectivo);

        return Inertia::render('anos-lectivos/edit', [
            'anoLectivo' => $anoLectivo,
        ]);
    }

    public function update(Request $request, AnoLectivo $anoLectivo)
    {
        $this->authorize('update', $anoLectivo);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:20', 'unique:ano_lectivos,nome,'.$anoLectivo->id],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after:data_inicio'],
            'activo' => ['boolean'],
        ]);

        if ($data['activo'] ?? false) {
            AnoLectivo::where('id', '!=', $anoLectivo->id)->where('activo', true)->update(['activo' => false]);
        }

        $anoLectivo->update($data);

        return redirect()->route('anos-lectivos.index')->with('success', 'Ano lectivo actualizado com sucesso.');
    }

    public function destroy(AnoLectivo $anoLectivo)
    {
        $this->authorize('delete', $anoLectivo);

        if ($anoLectivo->propinas()->exists()) {
            return back()->with('error', 'Não é possível apagar: existem propinas associadas a este ano lectivo.');
        }

        $anoLectivo->delete();

        return redirect()->route('anos-lectivos.index')->with('success', 'Ano lectivo removido com sucesso.');
    }
}
