<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrupoPap\DefinirDataDefesaRequest;
use App\Http\Requests\GrupoPap\StoreRequest;
use App\Http\Requests\GrupoPap\UpdateRequest;
use App\Http\Resources\GrupoPap\BancaResource;
use App\Http\Resources\GrupoPap\CreateResource;
use App\Http\Resources\GrupoPap\EditResource;
use App\Http\Resources\GrupoPap\ElementoResource;
use App\Http\Resources\GrupoPap\IndexResource;
use App\Http\Resources\GrupoPap\ShowResource;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService
    ) {
    }

    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        $user = Auth::guard('tenant')->user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

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
        ])->when($instituicaoId, fn($q) => $q->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn($q) => $q->where('instituicao_id', $instituicaoId)
            ))
            ->when($anoLectivoId, fn($q) => $q->whereHas(
                'turma',
                fn($q) => $q->where('ano_lectivo_id', $anoLectivoId)   // ← direto na turma, não via cursoClasseTurno
            ))
            ->when($user->hasRole('Aluno'), fn($q) => $q->whereHas(
                'alunos',
                fn($q) => $q->where('aluno_id', $user->aluno?->id)
            ))
            ->when(
                $user->hasRole('Professor') && !$user->hasPermissionTo('grupopap.viewAny'),
                fn($q) => $q->where(function ($q) use ($user) {
                    $professorId = $user->professor?->id;
                    $q->whereHas('turma.professores', fn($q) => $q->where('professores.id', $professorId))
                        ->orWhereHas('jurados', fn($q) => $q->where('professor_id', $professorId))
                        ->orWhere('professor_tutor_id', $professorId);
                })
            )
            ->latest()->paginate(10)->withQueryString();   // ← withQueryString para manter ano_lectivo_id na paginação

        $grupos->getCollection()->transform(function ($grupo) use ($user) {
            $grupo->can = [
                'view' => $user->can('view', $grupo),
                'update' => $user->can('update', $grupo),
                'delete' => $user->can('delete', $grupo),
                'definirData' => $user->can('definirData', $grupo),
                'definirTema' => $user->can('definirTema', $grupo),
            ];

            return $grupo;
        });

        return Inertia::render('tenant/pap/index', [
            'gruposPap' => IndexResource::collection($grupos),
            'anoLectivoId' => $anoLectivoId,          // ← adicionado
            'anosLectivos' => AnoLectivo::all(),      // ← adicionado
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
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

        $anoLectivoId = $turma->ano_lectivo_id; // ← Direto da turma

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        $alunosEmGrupo = ElementoGrupoPap::pluck('aluno_id');

        $alunos = Aluno::whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', function ($q) use ($turma) {
                $q->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true);
            })->with('inscricao.candidato:id,nome')->get()->map(fn($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id', 'nome'),
            'cursoClasse' => $cursoClasse->only('id', 'nome'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $anoLectivoId,          // ← NOVO
            'anosLectivos' => AnoLectivo::all(),      // ← NOVO
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
            'status_aprovacao' => GrupoPap::APROVACAO_RASCUNHO,
            'tema_grupo' => $request->tema_grupo,
            'problema' => $request->problema,
            'objectivos' => $request->objectivos,
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        $grupo->elementos()->createMany(
            collect($request->alunos)->map(fn($id) => ['aluno_id' => $id])->toArray()
        );

        return to_route('tenant.dashboard.pap.show', [
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

        $user = Auth::guard('tenant')->user();
        $anoLectivoId = $turma->ano_lectivo_id; // ← NOVO

        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
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

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,          // ← NOVO
            'anosLectivos' => AnoLectivo::all(),      // ← NOVO
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
                'update' => $user?->can('update', $grupoPap),
                'definirData' => $user?->can('definirData', $grupoPap),
                'delete' => $user?->can('delete', $grupoPap),
                'corrigirTema' => $user?->can('corrigirTema', $grupoPap),
                'aprovar' => $user?->can('aprovar', $grupoPap),
                'reprovar' => $user?->can('reprovar', $grupoPap),
                'solicitarMelhoria' => $user?->can('solicitarMelhoria', $grupoPap),
                'definirTema' => $user->can('definirTema', $grupoPap),
                'aprovarComoTutor' => $user?->can('aprovarComoTutor', $grupoPap),
                'elementos' => [
                    'create' => $user?->can('elementogrupopap.create'),
                    'atualizarNota' => $user?->can('elementogrupopap.atualizarNota')
                        && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id // ← adicionar
                        && !is_null($grupoPap->data_defesa)
                        && !$grupoPap->data_defesa->isFuture()
                        && $grupoPap->jurados()->exists(),
                    'delete' => $user?->can('elementogrupopap.delete'),
                ],
                // 'verBanca' => $grupoPap->instituicaoTutora()?->id === $user->instituicao_id,
                'verBanca' => $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
                    && !$user->hasRole('Aluno'),
                'banca' => [
                    'create' => $user?->can('create', [BancaJuriPap::class, $grupoPap]),
                    'update' => $user?->can('bancajuripap.update'),
                    'delete' => $user?->can('bancajuripap.delete'),
                ],
            ],
        ]);
    }

    /**
     * Mostra o formulário para editar os dados de um grupo da PAP.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('update', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id; // ← NOVO

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

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,          // ← NOVO
            'anosLectivos' => AnoLectivo::all(),      // ← NOVO
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

        return to_route('tenant.dashboard.pap.show', [
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
            'data_defesa' => $request->data_defesa . ' ' . $request->hora_defesa . ':00',
            'local_defesa' => $request->local_defesa,
        ]);

        return to_route('tenant.dashboard.pap.show', [
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

    public function editarTema(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('corrigirTema', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/editar-tema', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new ShowResource($grupoPap),
        ]);
    }

    public function actualizarTema(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('corrigirTema', $grupoPap);

        $validated = $request->validate([
            'tema_grupo' => 'required|string|max:255',
            'problema' => 'nullable|string',
            'objectivos' => 'nullable|string',
            'estudo_caso' => 'nullable|string',
        ]);

        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO, // ← volta ao tutor
        ]);

        return to_route('tenant.dashboard.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
                    'type' => 'success',
                    'message' => 'Tema corrigido. Aguarda revisão do professor tutor.',
                ]);
    }
}
