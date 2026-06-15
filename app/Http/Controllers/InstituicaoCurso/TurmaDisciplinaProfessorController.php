<?php

namespace App\Http\Controllers\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicaoCurso\StoreProfessorRequest;
use App\Http\Resources\ProfessorResource;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Professor;
use App\Models\Turma;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TurmaDisciplinaProfessorController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:professores.index', only: ['index']),
            new Middleware('permission:professores.show', only: ['show']),
            new Middleware('permission:professores.create', only: ['store']),
            new Middleware('permission:professores.edit', only: ['update']),
            new Middleware('permission:professores.delete', only: ['destroy']),
        ];
    }*/

    public function index(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::when(
            $instituicaoId,
            fn ($q) => $q->whereHas(
                'user',
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
        )->with(['user:id,nome,telefone'])
            ->get();

        // Usa o Resource para consistência
        return ProfessorResource::collection($professores);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $classeTurnoDisciplina->load('disciplina');

        $professores = Professor::with('user:id,nome')
            ->get()
            ->map(fn (Professor $professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ]);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/professores/create', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
            'professores' => $professores,
            'disciplinas' => [
                [
                    'id' => $classeTurnoDisciplina->id,
                    'disciplina' => [
                        'id' => $classeTurnoDisciplina->disciplina?->id,
                        'nome' => $classeTurnoDisciplina->disciplina?->nome,
                    ],
                ],
            ],
        ]);
    }

    public function store(
        StoreProfessorRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        if ($classeTurnoDisciplina->tem_professor) {
            return response()->json(['message' => 'Esta disciplina já tem professor.'], 422);
        }

        DB::transaction(function () use ($request, $classeTurnoDisciplina, $turma) {
            TurmaDisciplinaProfessor::create([
                'professor_id' => $request->professor_id,
                'turma_id' => $turma->id,
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id, // ← modelo injetado
            ]);

            $classeTurnoDisciplina->update(['tem_professor' => true]);
        });

        return to_route('turmas.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
        ])->with('success', 'Professor associado com sucesso.');
    }
}
