<?php

namespace App\Http\Controllers;

use App\Models\PautaStatus;
use App\Models\SolicitacaoEdicaoPauta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SolicitacaoEdicaoPautaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request)
    {
        $validated = $request->validate([
            'tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'periodo' => 'required|integer|in:1,2,3,4',
            'motivo' => 'required|string|max:500',
        ]);

        $status = PautaStatus::where('turma_disciplina_professor_id', $validated['tdp_id'])
            ->where('periodo', $validated['periodo'])
            ->firstOrFail();

        abort_unless($status->estaFinalizada(), 422, 'Pauta não está finalizada.');

        // Verificar se já tem pedido pendente
        $jaExiste = SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $validated['tdp_id'])
            ->where('periodo', $validated['periodo'])
            ->where('status', 'pendente')
            ->exists();

        abort_if($jaExiste, 422, 'Já tens um pedido pendente para este período.');

        SolicitacaoEdicaoPauta::create([
            ...$validated,
            'professor_user_id' => Auth::id(),
        ]);

        // Notificar director da instituição
        // $director->notify(new SolicitacaoEdicaoNotification(...));

        return back()->with('success', 'Pedido enviado ao director.');
    }

    // Director decide
    public function decidir(Request $request, SolicitacaoEdicaoPauta $solicitacao)
    {
        Gate::authorize('decidirSolicitacaoEdicao'); // permissão do director

        $validated = $request->validate([
            'decisao' => 'required|in:aprovada,rejeitada',
            'observacao' => 'nullable|string|max:500',
        ]);

        $solicitacao->update([
            'status' => $validated['decisao'],
            'decidido_por' => Auth::id(),
            'decidido_em' => now(),
            'observacao' => $validated['observacao'],
        ]);

        // Se aprovada: apenas a flag muda, o professor pode voltar a salvar
        // Notificar professor
        // $solicitacao->professor->notify(new DecisaoEdicaoNotification($solicitacao));

        return back()->with('success', 'Decisão registada.');
    }
    }