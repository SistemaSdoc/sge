<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrupoPap\DefinirDataDefesaRequest;
use App\Http\Requests\GrupoPap\StoreRequest;
use App\Http\Requests\GrupoPap\UpdateRequest;
use App\Http\Resources\GrupoPap\BancaResource;
use App\Http\Resources\GrupoPap\CreateResource;
use App\Http\Resources\GrupoPap\EditResource;
use App\Http\Resources\GrupoPap\ElementoResource;
use App\Http\Resources\GrupoPap\IndexResource;
use App\Http\Resources\GrupoPap\ShowResource;
use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\BancaJuriPap;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        $user = Auth::user();
        $instituicaoId = optional($user)->instituicaoFiltro() ?? null;

        // Filtro ano lectivo
        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $grupos = GrupoPap::with([
            'professor.user:id,nome',
            'turma.cursoClasseTurno.turno:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ])->when($instituicaoId, fn ($q) => $q->whereHas(
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
            fn ($q) => $q->where('instituicao_id', $instituicaoId)
        ))
            ->when($anoLectivoId, fn ($q) => $q->whereHas(
                'turma',
                fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->when(optional($user)->hasRole('Aluno') ?? false, fn ($q) => $q->whereHas(
                'alunos',
                fn ($q) => $q->where('aluno_id', optional($user->aluno)->id ?? null)
            ))
            ->when(optional($user)->hasRole('Professor') ?? false, fn ($q) => $q->where(function ($q) use ($user) {
                $professorId = optional($user->professor)->id ?? null;
                $q->whereHas('turma.professores', fn ($q) => $q->where('professores.id', $professorId))
                    ->orWhereHas('jurados', fn ($q) => $q->where('professor_id', $professorId))
                    ->orWhere('professor_tutor_id', $professorId);
            }))
            ->latest()->paginate(10)->withQueryString();

        $grupos->getCollection()->transform(function ($grupo) use ($user) {
            $grupo->can = [
                'view' => optional($user)->can('view', $grupo) ?? false,
                'update' => optional($user)->can('update', $grupo) ?? false,
                'delete' => optional($user)->can('delete', $grupo) ?? false,
                'definirData' => optional($user)->can('definirData', $grupo) ?? false,
            ];

            return $grupo;
        });

        return Inertia::render('pap/index', [
            'gruposPap' => IndexResource::collection($grupos),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'can' => [
                'create' => optional($user)->can('create', GrupoPap::class) ?? false,
            ],
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $anoLectivoId = $turma->ano_lectivo_id;

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        $alunosEmGrupo = ElementoGrupoPap::pluck('aluno_id');

        $alunos = Aluno::whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', function ($q) use ($turma) {
                $q->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true);
            })->with('inscricao.candidato:id,nome')->get()->map(fn ($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id', 'nome'),
            'cursoClasse' => $cursoClasse->only('id', 'nome'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new CreateResource((object) [
                'professores' => $professores,
                'alunos' => $alunos,
            ]),
        ]);
    }

    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $grupo = GrupoPap::create([
            'turma_id' => $turma->id,
            'professor_tutor_id' => $request->professor_tutor_id,
            'nome_grupo' => $request->nome_grupo,
            'tema_grupo' => $request->tema_grupo,
            'problema' => $request->problema,
            'objectivos' => $request->objectivos,
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        $grupo->elementos()->createMany(
            collect($request->alunos)->map(fn ($id) => ['aluno_id' => $id])->toArray()
        );

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupo->id,
        ]);
    }

    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('view', $grupoPap);

        $user = Auth::user();
        $anoLectivoId = $turma->ano_lectivo_id;

        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
        ]);

        $instituicaoTutoraModel = $grupoPap->instituicaoTutora();
        $instituicaoTutoraId = $instituicaoTutoraModel?->id;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;
        $siglaInstituto = $instituicaoTutoraModel?->sigla;

        $banca = $grupoPap->jurados()
            ->with('professor.user:id,nome,email')
            ->paginate(10, ['*'], 'page_banca');

        $elementos = $grupoPap->elementos()
            ->with('aluno.inscricao.candidato:id,nome,email', 'aluno:id,matricula,inscricao_id')
            ->paginate(10, ['*'], 'page_elementos');

        // Permissões com fallback
        $canUpdate = optional($user)->can('update', $grupoPap) ?? false;
        $canDefinirData = optional($user)->can('definirData', $grupoPap) ?? false;
        $canDelete = optional($user)->can('delete', $grupoPap) ?? false;
        $canCorrigirTema = optional($user)->can('corrigirTema', $grupoPap) ?? false;
        $canAprovar = optional($user)->can('aprovar', $grupoPap) ?? false;
        $canReprovar = optional($user)->can('reprovar', $grupoPap) ?? false;
        $canSolicitarMelhoria = optional($user)->can('solicitarMelhoria', $grupoPap) ?? false;
        $canElementoCreate = optional($user)->can('elementogrupopap.create') ?? false;
        $canElementoDelete = optional($user)->can('elementogrupopap.delete') ?? false;
        $canElementoAtualizarNota = optional($user)->can('elementogrupopap.atualizarNota') ?? false
            && optional($grupoPap->instituicaoTutora())->id === optional($user)->instituicao_id
            && ! is_null($grupoPap->data_defesa)
            && ! $grupoPap->data_defesa->isFuture()
            && $grupoPap->jurados()->exists();
        $isAluno = optional($user)->hasRole('Aluno') ?? false;
        $instituicaoIdUser = optional($user)->instituicao_id ?? null;
        $instituicaoTutoraId = optional($grupoPap->instituicaoTutora())->id ?? null;
        $verBanca = ($instituicaoTutoraId === $instituicaoIdUser && ! $isAluno);
        $canBancaCreate = optional($user)->can('create', [BancaJuriPap::class, $grupoPap]) ?? false;
        $canBancaUpdate = optional($user)->can('bancajuripap.update') ?? false;
        $canBancaDelete = optional($user)->can('bancajuripap.delete') ?? false;

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new ShowResource($grupoPap),
            'historico' => $grupoPap->historicoAprovacao->map(function ($item) use ($instituicaoTutoraId, $nomeCurso, $siglaInstituto) {
                $ehTutora = $item->estado_novo !== 'pendente'
                    && $item->utilizador?->instituicao_id === $instituicaoTutoraId;

                return [
                    'id' => $item->id,
                    'estado_anterior' => $item->estado_anterior,
                    'estado_novo' => $item->estado_novo,
                    'comentario' => $item->comentario,
                    'tema' => $item->tema,
                    'problema' => $item->problema,
                    'objectivos' => $item->objectivos,
                    'created_at' => $item->created_at?->toIso8601String(),
                    'utilizador' => [
                        'nome' => $ehTutora
                            ? "Grupo disciplinar do curso de {$nomeCurso} do {$siglaInstituto}"
                            : ($item->utilizador?->nome ?? '—'),
                    ],
                ];
            })->values(),
            'banca' => BancaResource::collection($banca),
            'elementos' => ElementoResource::collection($elementos),
            'can' => [
                'update' => $canUpdate,
                'definirData' => $canDefinirData,
                'delete' => $canDelete,
                'corrigirTema' => $canCorrigirTema,
                'aprovar' => $canAprovar,
                'reprovar' => $canReprovar,
                'solicitarMelhoria' => $canSolicitarMelhoria,
                'elementos' => [
                    'create' => $canElementoCreate,
                    'atualizarNota' => $canElementoAtualizarNota,
                    'delete' => $canElementoDelete,
                ],
                'verBanca' => $verBanca,
                'banca' => [
                    'create' => $canBancaCreate,
                    'update' => $canBancaUpdate,
                    'delete' => $canBancaDelete,
                ],
            ],
        ]);
    }

    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('update', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        $alunos = $turma->alunos()
            ->where(function ($query) use ($grupoPap) {
                $query->whereDoesntHave('grupoPap')
                    ->orWhereHas('grupoPap', function ($q) use ($grupoPap) {
                        $q->where('grupo_pap.id', $grupoPap->id);
                    });
            })
            ->get()
            ->map(function ($aluno) {
                return [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ];
            })
            ->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'form' => new EditResource((object) [
                'professores' => $professores,
                'alunos' => $alunos,
                'grupoPap' => $grupoPap,
            ]),
        ]);
    }

    public function update(
        UpdateRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('update', $grupoPap);

        $grupoPap->update($request->only([
            'nome_grupo',
            'tema_grupo',
            'estudo_caso',
            'status',
            'nota_final',
            'data_defesa',
            'professor_tutor_id',
        ]));

        if ($request->has('alunos')) {
            $grupoPap->alunos()->sync($request->alunos);
        }

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Grupo PAP actualizado com sucesso!',
        ]);
    }

    public function destroy(GrupoPap $grupoPap)
    {
        $this->authorize('delete', $grupoPap);
        $grupoPap->elementos()->delete();
        $grupoPap->jurados()->delete();
        $grupoPap->delete();

        return response()->json(['message' => 'Grupo PAP removido com sucesso.']);
    }

    public function definirData(
        DefinirDataDefesaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('definirData', $grupoPap);

        $grupoPap->update([
            'data_defesa' => $request->data_defesa.' '.$request->hora_defesa.':00',
            'local_defesa' => $request->local_defesa,
        ]);

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Data e local da defesa definidos com sucesso!',
        ]);
    }
}
