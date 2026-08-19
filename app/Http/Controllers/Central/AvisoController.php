<?php

namespace App\Http\Controllers\Central;

use App\Http\Requests\AvisoRequest;
use App\Models\Central\Aviso;
use App\Models\Central\GrupoPap;
use App\Models\Central\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AvisoController extends Controller
{
    // GET /api/avisos — painel admin
    public function index()
    {
        $this->authorize('viewAny', Aviso::class);

        /** @var User $user */
        $user = Auth::user();

        $instituicaoId = $user?->instituicaoFiltro();

        $avisos = Aviso::when(
            $instituicaoId,
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        )
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $avisos->getCollection()->transform(function ($aviso) use ($user) {
            return [
                ...$aviso->toArray(),
                'can' => [
                    'update' => $user->can('update', $aviso),
                    'delete' => $user->can('delete', $aviso),
                ],
            ];
        });

        return Inertia::render('avisos/index', [
            'avisos' => $avisos,
            'can' => [
                'create' => $user->can('create', Aviso::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Aviso::class);

        return Inertia::render('avisos/create');
    }

    // POST /api/avisos
    public function store(AvisoRequest $request)
    {
        $this->authorize('create', Aviso::class);

        /** @var User $user */
        $user = Auth::user();

        $aviso = Aviso::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'data' => $request->data,
            'ativo' => $request->ativo ?? true,
            'instituicao_id' => $user->instituicao_id,
            'destinatario' => $request->destinatario,
        ]);

        return to_route('avisos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aviso criado com sucesso!',
        ]);

    }

    // POST /api/avisos
    public function show(Aviso $aviso)
    {
        $this->authorize('view', $aviso);

        return Inertia::render('avisos.show', [
            'aviso' => $aviso,
        ]);
    }

    public function edit(Aviso $aviso)
    {
        $this->authorize('update', $aviso);

        return Inertia::render('avisos/edit', [
            'aviso' => $aviso,
        ]);
    }

    // PUT /api/avisos/{aviso}
    public function update(Request $request, Aviso $aviso)
    {
        $this->authorize('update', $aviso);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:aviso,evento,urgente',
            'data' => 'nullable|date',
            'ativo' => 'boolean',
            'destinatario' => 'required|in:todos,alunos,professores',
        ]);

        $aviso->update($request->only('titulo', 'descricao', 'tipo', 'data', 'ativo', 'destinatario'));

        return to_route('avisos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aviso actualizado com sucesso!',
        ]);

    }

    // DELETE /api/avisos/{aviso}
    public function destroy(Aviso $aviso)
    {
        $this->authorize('delete', $aviso);

        $aviso->delete();

        return to_route('avisos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aviso removido com sucesso!',
        ]);
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

        // return response()->json(['data' => $combined]);

        return Inertia::render('avisos/index', [
            'avisos' => $combined,
        ]);
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

        // return response()->json(['data' => $avisos]);

        return Inertia::render('avisos/index', [
            'avisos' => $avisos,
        ]);
    }
}
