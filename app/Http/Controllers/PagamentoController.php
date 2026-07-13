<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use App\Models\Propina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function index(Request $request, Propina $propina)
    {
        $this->authorize('view', $propina);

        return Inertia::render('Pagamento/Index', [
            'propina' => $propina->load('aluno:id,nome'),
            'pagamentos' => $propina->pagamentos()
                ->with('registadoPor:id,name')
                ->orderByDesc('data_pagamento')
                ->get()
                ->map(fn(Pagamento $p) => [
                    'id' => $p->id,
                    'valor_pago' => $p->valor_pago,
                    'data_pagamento' => $p->data_pagamento->format('Y-m-d'),
                    'metodo' => $p->metodo,
                    'comprovativo_path' => $p->comprovativo_path ? Storage::url($p->comprovativo_path) : null,
                    'registado_por' => $p->registadoPor?->name ?? '—',
                    'can' => [
                        'delete' => $request->user()->can('delete', $p),
                    ],
                ]),
        ]);
    }

    public function store(Request $request, Propina $propina)
    {
        $this->authorize('create', Pagamento::class);

        $data = $request->validate([
            'valor_pago' => ['required', 'numeric', 'min:0.01'],
            'data_pagamento' => ['required', 'date'],
            'metodo' => ['required', 'in:dinheiro,transferencia,multicaixa,outro'],
            'comprovativo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $totalPago = $propina->totalPago();
        if ($totalPago + $data['valor_pago'] > $propina->valor_devido) {
            return back()->with('error', 'O valor pago excede o valor devido da propina.');
        }

        $comprovativoPath = null;
        if ($request->hasFile('comprovativo')) {
            $comprovativoPath = $request->file('comprovativo')->store('comprovativos', 'public');
        }

        Pagamento::create([
            'propina_id' => $propina->id,
            'valor_pago' => $data['valor_pago'],
            'data_pagamento' => $data['data_pagamento'],
            'metodo' => $data['metodo'],
            'comprovativo_path' => $comprovativoPath,
            'registado_por' => $request->user()->id,
        ]);

        return back()->with('success', 'Pagamento registado com sucesso.');
    }

    public function destroy(Request $request, Pagamento $pagamento)
    {
        $this->authorize('delete', $pagamento);

        if ($pagamento->comprovativo_path) {
            Storage::disk('public')->delete($pagamento->comprovativo_path);
        }

        $pagamento->delete();

        return back()->with('success', 'Pagamento removido com sucesso.');
    }
}