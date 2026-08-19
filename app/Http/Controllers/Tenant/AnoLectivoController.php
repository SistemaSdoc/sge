<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Services\AnoLectivoConsistencyService;
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
                'data_inicio' => $ano->data_inicio->format('Y-m-d H:i'),
                'data_fim' => $ano->data_fim->format('Y-m-d H:i'),
                'estado' => $ano->estado,       // accessor: planeado | a_decorrer | encerrado
                'activo' => $ano->activo,       // accessor: planeado | a_decorrer | encerrado
                'can' => [
                    'update' => $request->user()->can('update', $ano) ?? true,
                    'delete' => $request->user()->can('delete', $ano) ?? true,
                ],
            ]);

        return Inertia::render('anos-lectivos/index', [
            'anosLectivos' => $anosLectivos,
        ]);
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

        app(AnoLectivoConsistencyService::class)->sincronizar();

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
