<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inscricao\StoreInscricaoRequest;
use App\Http\Requests\UpdateInscricaoRequest;
use App\Http\Resources\Inscricao\InscricaoResource;
use App\Http\Resources\Inscricao\InscricaoShowResource;
use App\Models\AnoLectivo;
use App\Models\CursoClasseTurno;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Services\InscricaoService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InscricaoController extends Controller
{
    public function __construct(
        private InscricaoService $inscricaoService
    ) {
    }

    public function index()
    {
        $this->authorize('viewAny', Inscricao::class);

        $user = Auth::user();
        $instituicaoId = Auth::user()?->instituicaoFiltro();

        // Ano lectivo ativo global
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;


        $inscricoes = Inscricao::with([
            'candidato:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'anoLectivo:id,nome',  
        ])->when(
                $instituicaoId,
                fn($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn($q) => $q->where('instituicao_id', $user->instituicao_id)
                )
            )->when(
                $anoLectivoId,
                fn($q) => $q->where('ano_lectivo_id', $anoLectivoId)
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
            'anosLectivos' => AnoLectivo::all(), // Todos os anos
            'anoLectivoActual' => $anoLectivoId,
            'can' => [
                'create' => $user->can('create', Inscricao::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Inscricao::class);

        $user = auth()->user();
        $instituicaoId = $user->instituicao_id;

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        // Busca curso_classe_turno apenas da instituição do utilizador, classe 10ª
        $cursoClasseTurnos = CursoClasseTurno::with([
            'turno:id,nome',
            'cursoClasse.classe:id,nome',
            'cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
        ])->whereHas(
                'cursoClasse.cursoTutelado.instituicaoCurso',
                fn($q) => $q->where('instituicao_id', $instituicaoId)
            )->get();

        // Agrupa por curso e filtra por classe 10ª
        $cursos = $cursoClasseTurnos
            ->filter(fn($cct) => $cct->cursoClasse->classe?->nome === '10ª')
            ->groupBy(function ($cct) {
                return $cct->cursoClasse->cursoTutelado->instituicaoCurso->id;
            })
            ->map(function ($group) {
                $primeiro = $group->first();

                return [
                    'id' => $primeiro->cursoClasse->cursoTutelado->instituicaoCurso->id,
                    'nome' => $primeiro->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
                    'turnos' => $group->map(fn($cct) => [
                        'id' => $cct->id,
                        'nome' => $cct->turno->nome,
                    ])->values()
                ];
            })
            ->values();

        return Inertia::render('inscricoes/create', [
            'cursos' => $cursos,
            'anoLectivoId' => $anoLectivoId,
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
            'anoLectivo:id,nome', 
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
