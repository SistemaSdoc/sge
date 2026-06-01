<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\GrupoPap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class AvisoController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:avisos.index', only: ['index', 'indexAluno']),
            new Middleware('permission:avisos.create', only: ['store']),
            new Middleware('permission:avisos.edit', only: ['update']),
            new Middleware('permission:avisos.delete', only: ['destroy']),
        ];
    }*/

    // GET /api/avisos — painel admin
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $avisos = Aviso::when(
            $instituicaoId,
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        )
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->tipo,
                'titulo' => $a->titulo,
                'descricao' => $a->descricao,
                'data' => $a->data?->toISOString(),
                'ativo' => $a->ativo,
                'destinatario' => $a->destinatario, // ← ADICIONAR
                'created_at' => $a->created_at->toISOString(),
            ]);

        return response()->json(['data' => $avisos]);
    }

    // POST /api/avisos
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:aviso,evento,urgente',
            'data' => 'nullable|date',
            'ativo' => 'boolean',
            'destinatario' => 'required|in:todos,alunos,professores',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $aviso = Aviso::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'data' => $request->data,
            'ativo' => $request->ativo ?? true,
            'instituicao_id' => $user?->instituicaoFiltro(),
            'destinatario' => $request->destinatario,
        ]);

        return response()->json(['data' => $aviso], 201);
    }

    // POST /api/avisos
    public function show(Aviso $aviso)
    {
        return response()->json(['data' => $aviso]);
    }

    // PUT /api/avisos/{aviso}
    public function update(Request $request, Aviso $aviso)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:aviso,evento,urgente',
            'data' => 'nullable|date',
            'ativo' => 'boolean',
            'destinatario' => 'required|in:todos,alunos,professores',
        ]);

        $aviso->update($request->only('titulo', 'descricao', 'tipo', 'data', 'ativo', 'destinatario'));

        return response()->json(status: 200);
    }

    // DELETE /api/avisos/{aviso}
    public function destroy(Aviso $aviso)
    {
        $aviso->delete();

        return response()->json(status: 200);
    }

    // GET /api/aluno/avisos — para o card do aluno
    public function indexAluno()
    {
        /** @var User $user */
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();
        $today = Carbon::today();

        // Avisos ativos
        $avisos = Aviso::where('ativo', true)
            ->whereIn('destinatario', ['todos', 'alunos'])
            ->when(
                $instituicaoId,
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->orderByRaw("FIELD(tipo, 'urgente', 'evento', 'aviso')")
            ->orderBy('data', 'asc')
            ->get()
            ->map(fn (Aviso $a) => [
                'id' => $a->id,
                'type' => $a->tipo,
                'titulo' => $a->titulo,
                'descricao' => $a->descricao,
                'data' => $a->data?->toISOString(),
            ]);

        // Eventos de defesa de PAP
        $eventos = GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->whereNotNull('data_defesa')
            ->whereDate('data_defesa', '>=', $today)
            ->orderBy('data_defesa')
            ->get()
            ->map(fn (GrupoPap $grupo) => [
                'id' => "pap-{$grupo->id}",
                'type' => 'evento',
                'titulo' => "Banca de Defesa - {$grupo->nome_grupo}",
                'descricao' => null,
                'data' => $grupo->data_defesa?->toISOString(),
            ]);

        // Combinar e ordenar por data
        $combined = collect($avisos)->concat($eventos)
            ->sortBy(function ($item) {
                return $item['data'] ?? now();
            })
            ->values();

        return response()->json(['data' => $combined]);
    }

    // GET /api/professor/avisos — para o card do professor
    public function indexProfessor()
    {
        /** @var User $user */
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        // Avisos ativos para professores
        $avisos = Aviso::where('ativo', true)
            ->whereIn('destinatario', ['todos', 'professores'])
            ->when(
                $instituicaoId,
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->orderByRaw("FIELD(tipo, 'urgente', 'evento', 'aviso')")
            ->orderBy('data', 'asc')
            ->get()
            ->map(fn (Aviso $a) => [
                'id' => $a->id,
                'type' => $a->tipo,
                'titulo' => $a->titulo,
                'descricao' => $a->descricao,
                'data' => $a->data?->toISOString(),
            ]);

        return response()->json(['data' => $avisos]);
    }
}
