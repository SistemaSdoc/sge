<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlunoTurmaResource;
use App\Http\Resources\ClasseTurnoDisciplinaResource;
use App\Http\Resources\GrupoPapIndexResource;
use App\Http\Resources\Turma\TurmaShowResource;
use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ClasseTurnoTurmaController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly PautaService $pautaService,
    ) {}

    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('viewAny', Turma::class);

        $user = Auth::user();

        // Filtro ano lectivo
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
            ->where('ano_lectivo_id', $anoLectivoId)  // ← Filtro
            ->when(
                $user->hasRole('Professor'),
                fn ($q) => $q->whereHas('professores', function ($q) use ($user) {
                    $q->where('professor_id', $user->professor->id);
                })
            )
            ->with([
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoTutora:id,nome',
                'cursoClasseTurno.turno:id,nome',
                'cursoClasseTurno.cursoClasse.classe:id,nome',
                'anoLectivo:id,nome',
            ])
            ->paginate(5);

        return Inertia::render('pautas/index', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso?->curso?->id,
                    'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome,
                ],
            ],
            'turmas' => $turmas->through(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
                'ano_lectivo' => $turma->anoLectivo?->nome,
                'cursoClasse' => ['id' => $turma->cursoClasseTurno?->cursoClasse?->id],
                'cursoClasseTurno' => ['id' => $turma->cursoClasseTurno?->id],
            ]),
            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        $cursoTutelado->load(['instituicaoCurso.curso', 'instituicaoTutora']);
        $cursoClasse->load('classe');
        $cursoClasseTurno->load('turno');

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/create', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome ?? 'Curso não encontrado',
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome ?? 'Classe não encontrado',
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome ?? 'Turno não encontrado',
            ],
            'can' => [
                'create' => Auth::user()->can('create', Turma::class),
            ],
        ]);
    }

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        Gate::authorize('create', Turma::class);

        $request->validate([
            'nome' => 'required|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        // Determina automaticamente o ano lectivo
        $anoLectivoId = $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $jaExiste = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->where('nome', $request->nome)
            ->exists();

        if ($jaExiste) {
            return back()->withErrors(['nome' => 'Já existe uma turma com este nome neste turno.']);
        }

        Turma::create([
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'ano_lectivo_id' => $anoLectivoId,
            'nome' => $request->nome,
            'max_alunos' => $request->max_alunos,
        ]);

        return redirect()->intended(route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
        ]))->with('success', 'Turma criada com sucesso!');
    }

    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        Gate::authorize('view', $turma);

        Redirect::setIntendedUrl(request()->fullUrl());

        $user = Auth::user();

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $turma->ano_lectivo_id;

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
            'gruposPap:id,turma_id,nome_grupo,tema_grupo,status,nota_final',
        ]);

        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with(['inscricao.candidato:id,nome', 'user:id,email,telefone'])
            ->paginate(10, ['*'], 'page_alunos');

        // Construir a query base de disciplinas
        $disciplinasQuery = $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($q) => $q->where('turma_id', $turma->id),
                'turmaDisciplinaProfessores.professor.user:id,nome',
                'horarios',
            ]);

        // Se é professor, filtrar apenas as disciplinas que ele leciona
        if ($user->hasRole('Professor')) {
            $professorId = $user->professor?->id;

            if ($professorId) {
                $disciplinasQuery->whereHas('turmaDisciplinaProfessores', fn ($q) => $q->where('professor_id', $professorId)
                    ->where('turma_id', $turma->id)
                );
            } else {
                $disciplinasQuery->whereRaw('0 = 1');
            }
        }

        $disciplinas = $disciplinasQuery->paginate(5, ['*'], 'page_disciplinas');

        $grupos = $turma->gruposPap()
            ->select('id', 'turma_id', 'nome_grupo', 'tema_grupo', 'status', 'nota_final')
            ->paginate(5, ['*'], 'page_grupos');

        $pautaRecurso = $this->pautaService->gerarPauta($turma, 4, 5);
        $podeLancarRecurso = $user->hasAnyRole(['Director', 'Subdirector'])
            || collect($pautaRecurso['alunos'] ?? [])
                ->contains(fn ($aluno) => is_null($aluno['nota_recurso'] ?? null));

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/show', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => [
                'id' => $cursoTutelado->only('id'),
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->only('id'),
                'nome' => $cursoClasse->classe->nome,
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome,
            ],
            'turma' => new TurmaShowResource($turma),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),

            'can' => [
                'curso' => [
                    'view' => $user->can('view', $cursoTutelado),
                ],
                'classe' => [
                    'view' => $user->can('view', $cursoClasse),
                ],
                'turno' => [
                    'view' => $user->can('view', $cursoClasseTurno),
                ],
                'alunos' => [
                    'create' => $user->can('create', Aluno::class),
                ],
                'disciplinas' => [
                    'create' => $user->can('create', ClasseTurnoDisciplina::class),
                ],
                'grupos' => [
                    'create' => $user->can('create', GrupoPap::class),
                ],
            ],

            'alunos' => AlunoTurmaResource::collection($alunos),
            'disciplinas' => ClasseTurnoDisciplinaResource::collection($disciplinas),
            'pautaRecurso' => $pautaRecurso,
            'pode_lancar_recurso' => $podeLancarRecurso,
            'grupos' => GrupoPapIndexResource::collection($grupos),
        ]);
    }

    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        return Inertia::render('cursos-tutelados/classes/turnos/turmas/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome ?? 'Curso não encontrado',
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome ?? 'Classe não encontrado',
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome ?? 'Turno não encontrado',
            ],
            'turma' => $turma,
            'origem' => request('origem'),
            'can' => [
                'update' => Auth::user()->can('update', $turma),
            ],
        ]);
    }

    public function update(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        Gate::authorize('update', $turma);

        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        $turma->update(array_filter([
            'nome' => $request->input('nome', $turma->nome),
            'max_alunos' => $request->input('max_alunos', $turma->max_alunos),
        ], fn ($value) => $value !== null));

        // Preserva o filtro de ano lectivo na navegação de volta
        $anoLectivoParam = $turma->ano_lectivo_id ? ['ano_lectivo_id' => $turma->ano_lectivo_id] : [];

        if ($request->origem === 'turma') {
            return to_route('turmas.show', [
                'instituicao' => $instituicao,
                'cursoTutelado' => $cursoTutelado,
                'cursoClasse' => $cursoClasse,
                'cursoClasseTurno' => $cursoClasseTurno,
                'turma' => $turma,
            ] + $anoLectivoParam);
        }

        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao,
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'turno' => $cursoClasseTurno->id,
        ] + $anoLectivoParam);
    }

    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        Gate::authorize('delete', $turma);

        if ($turma->alunos()->exists()) {
            return back()->withErrors([
                'turma' => 'Não é possível remover uma turma que tem alunos associados.',
            ]);
        }

        $turma->delete();

        // Preservar filtro no redirect usando o ano da turma
        $anoLectivoParam = $turma->ano_lectivo_id ? ['ano_lectivo_id' => $turma->ano_lectivo_id] : [];

        return to_route('turmaGeral', $anoLectivoParam);
    }
}
