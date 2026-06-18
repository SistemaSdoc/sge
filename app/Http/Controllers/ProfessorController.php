<?php

namespace App\Http\Controllers;

use App\Http\Requests\Professor\StoreProfessoresRequest;
use App\Http\Requests\Professor\UpdateProfessoresRequest;
use App\Models\Professor;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfessorController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:professores.index',  only: ['index']),
            new Middleware('permission:professores.show',   only: ['show']),
            new Middleware('permission:professores.create', only: ['store']),
            new Middleware('permission:professores.edit',   only: ['update']),
            new Middleware('permission:professores.delete', only: ['destroy']),
        ];
    }*/

    public function index()
    {

        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::select(['id', 'user_id', 'especialidade', 'created_at'])
            ->with(['user:id,nome,telefone'])
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->orderBy('created_at', 'asc')
            ->paginate(1);

        return Inertia::render('professores/index', [
            'professores' => $professores,
        ]);
    }

    public function create()
    {
        return Inertia::render('professores/create');
    }

    public function store(StoreProfessoresRequest $request)
    {
        $request->validated();

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'bi' => $request->bi,
            'telefone' => $request->telefone,
            'password' => Hash::make('123456'),
            'instituicao_id' => Auth::user()->instituicao_id,
        ]);

        $roleProfessor = Role::where('nome', 'Professor')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$roleProfessor->id]);

        Professor::create([
            'user_id' => $user->id,
            'especialidade' => $request->especialidade,
        ]);

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor criado com sucesso.',
        ]);
    }

    public function show(Professor $professor)
    {
        $professor->load([
            'user:id,nome,email,bi,telefone',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ]);

        $cursos = $professor->turmaDisciplinaProfessor->map(function ($item) {
            $curso = $item->classeTurnoDisciplina?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso;
            if (! $curso) {
                return null;
            }

            return [
                'id' => $curso->id,
                'nome' => $curso->nome,
            ];
        })->filter()->unique('id')->values();

        $turmas = Turma::with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->whereHas('turmaDisciplinaProfessor', function ($q) use ($professor) {
                $q->where('professor_id', $professor->id);
            })
            ->get()
            ->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
            ]);

        return Inertia::render('professores/show', [
            'professor' => $professor,
            'cursos' => $cursos,
            'turmas' => $turmas,
        ]);
    }

    public function edit(Professor $professor)
    {
        return Inertia::render('professores/edit', [
            'professor' => $professor->load('user:id,nome,email,bi,telefone'),
        ]);
    }

    public function update(UpdateProfessoresRequest $request, Professor $professor)
    {
        $request->validated();

        // Query Builder — não sofre do bug do $incrementing = false
        User::where('id', $professor->user_id)->update([
            'nome' => $request->nome,
            'email' => $request->email,
            'bi' => $request->bi,
            'telefone' => $request->telefone,
        ]);

        $professor->update([
            'especialidade' => $request->especialidade,
        ]);

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor atualizado com sucesso.',
        ]);
    }

    public function destroy(Professor $professor)
    {
        $professor->delete();

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor removido com sucesso.',
        ]);
    }
}