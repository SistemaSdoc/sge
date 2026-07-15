<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\Pagamento;
use App\Models\Propina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function index(Request $request, Propina $propina)
    {
        $this->authorize('viewAny', Pagamento::class);

        return Inertia::render('pagamentos/index', [
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

        public function create()
    {
        $this->authorize('create', Pagamento::class);

        $user = Auth::user();

        $alunos = Aluno::query()
            ->whereHas('user', fn ($query) => $query->where('instituicao_id', $user->instituicao_id))
            ->with(['user'])
            ->get()
            ->map(function (Aluno $aluno) {
                $turma = $aluno->turmaActual()->first();
                $cursoClasseTurno = $turma?->cursoClasseTurno;
                $cursoClasse = $cursoClasseTurno?->cursoClasse;
                $curso = $cursoClasse?->cursoTutelado?->instituicaoCurso?->curso;

                return [
                    'id' => $aluno->id,
                    'nome' => $aluno->user?->name ?? $aluno->user?->nome ?? 'Sem nome',
                    'curso' => $curso?->nome ?? '—',
                    'classe' => $cursoClasse?->classe?->nome ?? '—',
                    'turno' => $cursoClasseTurno?->turno?->nome ?? '—',
                    'turma' => $turma?->nome ?? '—',
                ];
            });

        $itensPagaveis = ItemPagavel::query()
            ->where('instituicao_id', $user->instituicao_id)
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo', 'valor_padrao']);

        return Inertia::render('pagamentos/create', [
            'alunos' => $alunos,
            'itensPagaveis' => $itensPagaveis,
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