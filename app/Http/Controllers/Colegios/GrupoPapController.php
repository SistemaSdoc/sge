<?php

namespace App\Http\Controllers\Colegios;

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
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GrupoPapController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', GrupoPap::class);

        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        // Filtro ano lectivo
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::where('activo', 1)->first()?->id;

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
            ->latest()->paginate(10)->withQueryString();   // ← withQueryString para manter ano_lectivo_id na paginação

        $grupos->getCollection()->transform(function ($grupo) use ($user) {
            $grupo->can = [
                'view' => $user->can('view', $grupo),
                'update' => $user->can('update', $grupo),
                'delete' => $user->can('delete', $grupo),
                'definirData' => $user->can('definirData', $grupo),
            ];

            return $grupo;
        });

        return Inertia::render('pap/index', [
            'gruposPap' => IndexResource::collection($grupos),
            'anoLectivoId' => $anoLectivoId,          // ← adicionado
            'anosLectivos' => AnoLectivo::all(),      // ← adicionado
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
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
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        $grupo->elementos()->createMany(
            collect($request->alunos)->map(fn($id) => ['aluno_id' => $id])->toArray()
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
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('view', $grupoPap);

        $user = Auth::user();

        // Buscar o colégio tutelado
        $colegioModel = Instituicao::findOrFail($colegio);

        // Ano lectivo da turma
        $anoLectivoId = $turma->ano_lectivo_id;

        // Buscar dados da instituição tutora e do curso tutelado
        $instituicaoTutoraModel = $grupoPap->instituicaoTutora();
        $instituicaoTutoraId = $instituicaoTutoraModel?->id;
        $siglaInstituto = $instituicaoTutoraModel?->sigla;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;

        // Carregar dados do grupo PAP
        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
        ]);

        // Buscar banca
        $banca = $grupoPap->jurados()
            ->with('professor.user:id,nome,email')
            ->paginate(10, ['*'], 'page_banca');

        // Buscar elementos do grupo
        $elementos = $grupoPap->elementos()
            ->with([
                'aluno.inscricao.candidato:id,nome,email',
                'aluno:id,matricula,inscricao_id',
            ])
            ->paginate(10, ['*'], 'page_elementos');

        return Inertia::render(
            'colegio/cursos-tutelados/classes/turnos/turmas/pap/show',
            [
                // Instituição tutora
                'instituicao' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                    'sigla' => $instituicao->sigla,
                ],

                // Colégio tutelado
                'colegio' => [
                    'id' => $colegioModel->id,
                    'nome' => $colegioModel->nome,
                ],

                // Curso tutelado
                'cursoTutelado' => [
                    'id' => $cursoTutelado->id,
                    'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome,
                ],

                // Classe
                'cursoClasse' => [
                    'id' => $cursoClasse->id,
                ],

                // Turno
                'cursoClasseTurno' => [
                    'id' => $cursoClasseTurno->id,
                ],

                // Turma
                'turma' => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                ],

                // Ano lectivo
                'anoLectivoId' => $anoLectivoId,
                'anosLectivos' => AnoLectivo::all(),

                // Dados do PAP
                'grupoPap' => new ShowResource($grupoPap),
                'historico' => $grupoPap->historicoAprovacao->map(function ($item) use ($instituicaoTutoraId, $nomeCurso, $siglaInstituto) {
                    $ehTutora = $item->utilizador?->instituicao_id === $instituicaoTutoraId;
                    return [
                        'id' => $item->id,
                        'estado_anterior' => $item->estado_anterior,
                        'estado_novo' => $item->estado_novo,
                        'comentario' => $item->comentario,
                        'tema' => $item->tema,
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

                // Permissões
                'can' => [
                    'update' => $user?->can('update', $grupoPap),
                    'definirData' => $user?->can('definirData', $grupoPap),
                    'delete' => $user?->can('delete', $grupoPap),
                    'corrigirTema' => $user?->can('corrigirTema', $grupoPap),
                    'aprovar' => $user?->can('aprovar', $grupoPap),           // ← adicionar
                    'reprovar' => $user?->can('reprovar', $grupoPap),          // ← adicionar
                    'solicitarMelhoria' => $user?->can('solicitarMelhoria', $grupoPap), // ← adicionar


                    'elementos' => [
                        'create' => $user?->can('elementogrupopap.create'),
                        'atualizarNota' => $user?->can('elementogrupopap.atualizarNota')
                            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
                            && !is_null($grupoPap->data_defesa)
                            && !$grupoPap->data_defesa->isFuture()
                            && $grupoPap->jurados()->exists(),
                        'delete' => $user?->can('elementogrupopap.delete'),
                    ],

                    'verBanca' => $grupoPap->instituicaoTutora()?->id === $user->instituicao_id,

                    'banca' => [
                        'create' => $user?->can('create', [BancaJuriPap::class, $grupoPap]),
                        'update' => $user?->can('bancajuripap.update'),
                        'delete' => $user?->can('bancajuripap.delete'),
                    ],
                ],
            ]
        );
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
