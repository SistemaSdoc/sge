<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlunoTurmaResource;
use App\Http\Resources\ClasseTurnoDisciplinaResource;
use App\Http\Resources\Turma\TurmaShowResource;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ClasseTurnoTurmaController extends Controller /* implements HasMiddleware */
{
    public function __construct(
        private readonly PautaService $pautaService,
    ) {
    }
    /* public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index',  only: ['index']),
            new Middleware('permission:turmas.create', only: ['store']),
            new Middleware('permission:turmas.edit',   only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy']),
        ];
    } */

    /**
     * Display a listing of the resource.
     */
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
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
            // Usar through() em vez de map()->toArray() para preservar a paginação
            'turmas' => $turmas->through(fn($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
                'cursoClasse' => ['id' => $turma->cursoClasseTurno?->cursoClasse?->id],
                'cursoClasseTurno' => ['id' => $turma->cursoClasseTurno?->id],
            ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        // Carrega as relações necessárias
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
                // O nome do curso vem através da relação instituicaoCurso -> curso
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
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

        // return back()->with('success', 'Turma criada com sucesso!');
        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
        ])->with('success', 'Turma criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'gruposPap:id,turma_id,nome_grupo,tema_grupo,status,nota_final',
        ]);

        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with(['inscricao.candidato:id,nome', 'user:id,email,telefone'])
            ->paginate(5, ['*'], 'page_alunos');

        $disciplinas = $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn($q) => $q->where('turma_id', $turma->id),
                'turmaDisciplinaProfessores.professor.user:id,nome',
                'horarios',
            ])
            ->paginate(5, ['*'], 'page_disciplinas');

        $pautaRecurso = $this->pautaService->gerarPauta($turma, 4, 5);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/show', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => new TurmaShowResource($turma),
            'alunos' => [
                ...$alunos->toArray(),
                'data' => AlunoTurmaResource::collection($alunos->items())->resolve(),
            ],
            'disciplinas' => [
                ...$disciplinas->toArray(),
                'data' => ClasseTurnoDisciplinaResource::collection($disciplinas->items())->resolve(),
            ],
            'pautaRecurso' => $pautaRecurso,
            'grupos' => [
                ...$grupos->toArray(),
                'data' => $grupos->items(),
            ],
        ]);

    }

    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        return Inertia::render(
            'cursos-tutelados/classes/turnos/turmas/edit',
            [
                'turma' => $turma,
                'instituicaoId' => $instituicao->id,
                'cursoId' => $cursoTutelado->id,
                'classeId' => $cursoClasse->id,
                'turnoId' => $cursoClasseTurno->id,
                'origem' => request('origem'),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        $temAlunos = $turma->alunos()->exists();

        if ($temAlunos) {
            return response()->json([
                'message' => 'Não é possível remover uma turma que tem alunos associados.',
            ], 422);
        }

        $turma->delete();

        return response()->json(status: 200);
    } 
}
