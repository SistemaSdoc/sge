<?php

namespace App\Http\Controllers;

use App\Models\PautaStatus;
use App\Models\PeriodoLancamentoNotas;
use App\Models\SolicitacaoEdicaoPauta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SolicitacaoEdicaoPautaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // App/Http/Controllers/SolicitacaoEdicaoPautaController.php
    public function index(Request $request)
    {

        $this->authorize('viewAny', SolicitacaoEdicaoPauta::class);

        abort_unless($request->user()->hasAnyRole(['Director', 'Subdirector']), 403);

        $solicitacoes = SolicitacaoEdicaoPauta::with([
            'turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'professor', // user
        ])
            ->where('status', 'pendente')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'tipo' => $s->tipo,
                'periodo' => $s->periodo,
                'motivo' => $s->motivo,
                'created_at' => $s->created_at->format('d/m/Y H:i'),
                'professor' => $s->professor->nome ?? '—',
                'disciplina' => $s->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->nome ?? '—',
                'turma' => $s->turmaDisciplinaProfessor->turma->nome ?? '—',
                'link_prazos' => route('prazos-lancamento-notas.edit', [
                    'instituicao' => $s->turmaDisciplinaProfessor->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicao_tutora_id,
                ]),
            ]);

        return Inertia::render('pautas/solicitacoes/index', [
            'solicitacoes' => $solicitacoes,
            '',
        ]);
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
            'tipo' => 'required|in:reabertura_edicao,extensao_prazo',
        ]);

        // Verificar se o estado da pauta justifica o tipo de solicitação
        $status = PautaStatus::where('turma_disciplina_professor_id', $validated['tdp_id'])
            ->where('periodo', $validated['periodo'])
            ->first();

        $estaFinalizada = $status?->status === 'finalizada';
        $estaExpirada = $status?->status === 'expirada';

        if ($validated['tipo'] === 'reabertura_edicao') {
            abort_unless($estaFinalizada || $estaExpirada, 422, 'A pauta não está finalizada nem expirada.');
        }

        // Verificar duplicado filtrando por tipo
        $jaExiste = SolicitacaoEdicaoPauta::where('turma_disciplina_professor_id', $validated['tdp_id'])
            ->where('periodo', $validated['periodo'])
            ->where('tipo', $validated['tipo'])
            ->where('status', 'pendente')
            ->exists();

        abort_if($jaExiste, 422, 'Já tens um pedido pendente deste tipo para este período.');

        SolicitacaoEdicaoPauta::create([
            'turma_disciplina_professor_id' => $validated['tdp_id'],
            'periodo' => $validated['periodo'],
            'tipo' => $validated['tipo'],
            'professor_user_id' => Auth::id(),
            'motivo' => $validated['motivo'],
            'status' => 'pendente',
        ]);

        return back()->with('success', 'Pedido enviado ao director.');
    }

    // Decidir uma solicitação de edição de pauta (aprovada ou rejeitada)
    public function decidir(Request $request, SolicitacaoEdicaoPauta $solicitacao)
    {
        abort_unless($request->user()->hasAnyRole(['Director', 'Subdirector']), 403);

        $validated = $request->validate([
            'decisao' => 'required|in:aprovada,rejeitada',
            'observacao' => 'nullable|string|max:500',
            'prazo_edicao_ate' => 'required_if:decisao,aprovada|nullable|date|after:now',
        ]);

        $solicitacao->update([
            'status' => $validated['decisao'],
            'decidido_por' => Auth::id(),
            'decidido_em' => now(),
            'observacao' => $validated['observacao'] ?? null,
            'prazo_edicao_ate' => $validated['prazo_edicao_ate'] ?? null,
        ]);

        if ($validated['decisao'] === 'aprovada') {
            // Reverter pauta para rascunho em ambos os casos
            PautaStatus::where('turma_disciplina_professor_id', $solicitacao->turma_disciplina_professor_id)
                ->where('periodo', $solicitacao->periodo)
                ->whereIn('status', ['finalizada', 'expirada'])
                ->update([
                    'status' => 'rascunho',
                    'finalizada_em' => null,
                    'finalizada_automaticamente' => false,
                ]);

            if ($solicitacao->tipo === 'extensao_prazo') {
                $instituicaoId = $solicitacao->turmaDisciplinaProfessor
                    ->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicao_tutora_id;

                // Actualizar data_limite do período com o prazo definido pelo director
                PeriodoLancamentoNotas::where('instituicao_id', $instituicaoId)
                    ->where('periodo', $solicitacao->periodo)
                    ->update(['data_limite' => $validated['prazo_edicao_ate']]);

                return back()->with('success', "Extensão aprovada até {$validated['prazo_edicao_ate']}. O professor já pode lançar.");
            }

            if ($solicitacao->tipo === 'reabertura_edicao') {
                return back()->with('success', 'Reabertura aprovada. O professor pode editar até o prazo definido.');
            }
        }

        return back()->with('success', 'Decisão registada.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    // Director decide
    // public function decidir(Request $request, SolicitacaoEdicaoPauta $solicitacao)
    // {
    //     Gate::authorize('decidirSolicitacaoEdicao'); // permissão do director

    //     $validated = $request->validate([
    //         'decisao' => 'required|in:aprovada,rejeitada',
    //         'observacao' => 'nullable|string|max:500',
    //     ]);

    //     $solicitacao->update([
    //         'status' => $validated['decisao'],
    //         'decidido_por' => Auth::id(),
    //         'decidido_em' => now(),
    //         'observacao' => $validated['observacao'],
    //     ]);

    //     // Se aprovada: apenas a flag muda, o professor pode voltar a salvar
    //     // Notificar professor
    //     // $solicitacao->professor->notify(new DecisaoEdicaoNotification($solicitacao));

    //     return back()->with('success', 'Decisão registada.');
    // }
}
