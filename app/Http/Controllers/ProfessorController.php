<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfessoresRequest;
use App\Http\Resources\ProfessorResource;
use App\Models\Curso;
use App\Models\Professor;
use App\Models\Turma;

use App\Models\User;
use App\Models\Role;
use App\Models\TurnoDisciplinaProfessor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProfessorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:professores.index',  only: ['index']),
            new Middleware('permission:professores.show',   only: ['show']),
            new Middleware('permission:professores.create', only: ['store']),
            new Middleware('permission:professores.edit',   only: ['update']),
            new Middleware('permission:professores.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::when(
            $instituicaoId,
            fn($q) => $q->whereHas(
                'user',
                fn($q) => $q->where('instituicao_id', $instituicaoId)
            )
        )->with(['user:id,nome,telefone'])
            ->get();

        // Usa o Resource para consistência
        return ProfessorResource::collection($professores);
    }

    public function store(ProfessoresRequest $request)
    {
        $request->validated();

        // 1. Criar USER
        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'bi' => $request->bi,
            'telefone' => $request->telefone,
            'password' => Hash::make('123456'),
            'instituicao_id' => Auth::user()->instituicao_id,
        ]);

        // 2. Atribuir role professor
        $roleProfessor = Role::where('nome', 'Professor')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$roleProfessor->id]);

        Professor::create([
            'user_id' => $user->id,
            'especialidade' => $request->especialidade,
        ]);

        return response()->json(status: 201);
    }

    public function show(Professor $professor)
    {
        // 🔹 Carregar dados básicos (SEM turmas para evitar duplicação)
        $professor->load([
            'user:id,nome,email,bi,telefone',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ]);

        // Buscar cursos únicos
        // ✅ Depois
        $cursos = $professor->turmaDisciplinaProfessor->map(function ($item) {
            $curso = $item->classeTurnoDisciplina?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso;
            if (!$curso) return null;
            return [
                'id'   => $curso->id,
                'nome' => $curso->nome,
            ];
        })->filter()->unique('id')->values();

        // Buscar turmas SEM DUPLICAÇÃO
        $turmas = Turma::with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->whereHas('turmaDisciplinaProfessor', function ($q) use ($professor) {
                $q->where('professor_id', $professor->id);
            })
            ->get()
            ->map(fn($turma) => [
                'id'     => $turma->id,
                'nome'   => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno'  => $turma->cursoClasseTurno?->turno?->nome,
            ]);

        // Resposta final organizada
        return response()->json([
            'id'            => $professor->id,
            'especialidade' => $professor->especialidade,
            'user' => [
                'nome'     => $professor->user->nome,
                'email'    => $professor->user->email,
                'bi'       => $professor->user->bi,
                'telefone' => $professor->user->telefone,
            ],
            'turmas'  => $turmas,
            'cursos'  => $cursos,
            'turnos'  => $professor->turmaDisciplinaProfessor->map(function ($item) {
                $turno = $item->classeTurnoDisciplina?->cursoClasseTurno?->turno;
                if (!$turno) return null;
                return [
                    'id'   => $turno->id,
                    'nome' => $turno->nome,
                ];
            })->filter()->unique('id')->values(),
        ], 200);
    }

    public function edit(Professor $professor)
    {
        $professor->load(['turmas', 'cursos', 'turnoDisciplinaProfessor',]);

        // Todos os turnos disponíveis (para o select)
        $turnoDisciplinaProfessor = TurnoDisciplinaProfessor::with([
            'turno',
            'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado.instituicaoCurso.curso', // pega o nome real do curso
            'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado.instituicaoCurso.instituicao',
        ])->get();

        $cursos = Curso::all();
        $turmas = Turma::all();

        return response()->json([
            'professor' => $professor,
            'turmas' => $turmas,
            'cursos' => $cursos,
            'turnoDisciplinaProfessor' => $turnoDisciplinaProfessor
        ], status: 200);
    }

    public function update(ProfessoresRequest $request, Professor $professor)
    {
        $request->validated();

        // 1. Atualizar USER
        $professor->user->update([
            'nome' => $request->nome,
            'email' => $request->email,
            'bi' => $request->bi,
            'telefone' => $request->telefone,
        ]);

        // 2. Atualizar PROFESSOR
        $professor->update([
            'especialidade' => $request->especialidade,
        ]);

        return response()->json(status: 200);
    }

    public function destroy(Professor $professor)
    {
        $professor->delete();

        return response()->json(status: 200);
    }
}
