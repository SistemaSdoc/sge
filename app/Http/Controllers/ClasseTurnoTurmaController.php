<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlunoTurmaResource;
use App\Http\Resources\ClasseTurnoDisciplinaResource;
use App\Http\Resources\GrupoPapIndexResource;
use App\Http\Resources\Turma\TurmaShowResource;
use App\Models\Aluno;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClasseTurnoTurmaController extends Controller
{
    public function __construct(
        private readonly PautaService $pautaService,
    ) {}

    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('viewAny', Turma::class);

        $user = Auth::user();

        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
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
                'cursoClasse' => ['id' => $turma->cursoClasseTurno?->cursoClasse?->id],
                'cursoClasseTurno' => ['id' => $turma->cursoClasseTurno?->id],
            ]),
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        // Gate::authorize('create', Turma::class);

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
                'nome' => $cursoClasse->classe->nome ?? 'Classe não encontrada',
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

        $jaExiste = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->where('nome', $request->nome)
            ->exists();

        if ($jaExiste) {
            return back()->withErrors(['nome' => 'Já existe uma turma com este nome neste turno.']);
        }

        Turma::create([
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'nome' => $request->nome,
            'max_alunos' => $request->max_alunos,
        ]);

        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
        ])->with('success', 'Turma criada com sucesso!');
    }

    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        Gate::authorize('view', $turma);

        $user = Auth::user();

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'gruposPap:id,turma_id,nome_grupo,tema_grupo,status,nota_final',
        ]);

        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with(['inscricao.candidato:id,nome', 'user:id,email,telefone'])
            ->paginate(1, ['*'], 'page_alunos');

        $disciplinasQuery = $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($q) => $q->where('turma_id', $turma->id),
                'turmaDisciplinaProfessores.professor.user:id,nome',
                'horarios',
            ]);

        if ($user->hasRole('Professor')) {
            $professorId = $user->professor?->id;

            if (! $professorId) {
                $disciplinasQuery->whereRaw('0 = 1');
            }

            // TODO: quando existir $professorId, filtrar as disciplinas
            // atribuídas a esse professor nesta turma.
        }

        $disciplinas = $disciplinasQuery->paginate(5, ['*'], 'page_disciplinas');

        $grupos = $turma->gruposPap()
            ->select('id', 'turma_id', 'nome_grupo', 'tema_grupo', 'status', 'nota_final')
            ->paginate(5, ['*'], 'page_grupos');

        $pautaRecurso = $this->pautaService->gerarPauta($turma, 4, 5);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/show', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => new TurmaShowResource($turma),

            'can' => [
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
        // Gate::authorize('update', $turma);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/edit', [
            'turma' => $turma,
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'classeId' => $cursoClasse->id,
            'turnoId' => $cursoClasseTurno->id,
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

        $turma->update($request->only(['nome', 'max_alunos']));

        if ($request->origem === 'turma') {
            return to_route('turmas.show', [
                'instituicao' => $instituicao,
                'cursoTutelado' => $cursoTutelado,
                'cursoClasse' => $cursoClasse,
                'cursoClasseTurno' => $cursoClasseTurno,
                'turma' => $turma,
            ]);
        }

        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao,
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'turno' => $cursoClasseTurno->id,
        ]);
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

        return to_route('turmaGeral');
    }
}
