<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inscricao\StoreInscricaoRequest;
use App\Http\Requests\UpdateInscricaoRequest;
use App\Http\Resources\Inscricao\InscricaoResource;
use App\Http\Resources\Inscricao\InscricaoShowResource;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Services\InscricaoService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InscricaoController extends Controller
{
    public function __construct(
        private InscricaoService $inscricaoService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Inscricao::class);

        $user = Auth::user();
        $instituicaoId = Auth::user()?->instituicaoFiltro();

        $inscricoes = Inscricao::with([
            'candidato:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ])->when(
            $instituicaoId,
            fn ($q) => $q->whereHas(
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
        )->latest()->paginate(10);

        $inscricoes->getCollection()->transform(function ($inscricao) use ($user) {
            $inscricao->can = [
                'view' => $user->can('view', $inscricao),
                'update' => $user->can('update', $inscricao),
                'delete' => $user->can('delete', $inscricao),
            ];

            return $inscricao;
        });

        return Inertia::render('inscricoes/index', [
            'inscricoes' => [
                'data' => InscricaoResource::collection($inscricoes->items())->toArray(request()),
                'current_page' => $inscricoes->currentPage(),
                'last_page' => $inscricoes->lastPage(),
            ],
            'can' => [
                'create' => $user->can('create', Inscricao::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Inscricao::class);

        $user = auth()->user();

        $instituicao = Instituicao::with([
            'instituicaoCursos.curso',
            'instituicaoCursos.cursoTutelado.cursoClasses.classe:id,nome',
            'instituicaoCursos.cursoTutelado.cursoClasses.turnos.turno:id,nome',
        ])->findOrFail($user->instituicao_id);

        $cursos = $instituicao->instituicaoCursos->map(fn ($ci) => [
            'id' => $ci->id,
            'nome' => $ci->curso->nome,
            'turnos' => $ci->cursoTutelado?->cursoClasses
                ->filter(fn ($c) => $c->classe?->nome === '10ª')
                ->flatMap(fn ($c) => $c->turnos->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->turno->nome,
                ]))->values(),
        ])->filter(fn ($ci) => ! empty($ci['turnos']) && $ci['turnos']->isNotEmpty())->values();

        return Inertia::render('inscricoes/create', [
            'cursos' => $cursos,
        ]);
    }

    public function store(StoreInscricaoRequest $request)
    {
        $this->authorize('create', Inscricao::class);

        $this->inscricaoService->criar($request->validated());

        return redirect()->route('inscricoes.index');
    }

    public function show(Inscricao $inscricao)
    {
        $this->authorize('view', $inscricao);

        $inscricao->load([
            'candidato:id,nome,bi,numero_estudante,email,telefone,morada',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ]);

        return Inertia::render('inscricoes/show', [
            'inscricao' => (new InscricaoShowResource($inscricao))->resolve(),  // AAlterado, erro que estava a fazer aparecer tela preta ao acessar detalhes da inscrição
        ]);

    }

    public function update(UpdateInscricaoRequest $request, Inscricao $inscricao)
    {
        $this->authorize('update', $inscricao);

        $this->inscricaoService->atualizarNotaTeste($inscricao, $request->validated('nota_teste'));

        return redirect()->route('inscricoes.index');
    }

    public function destroy(Inscricao $inscricao)
    {
        //
    }
}
