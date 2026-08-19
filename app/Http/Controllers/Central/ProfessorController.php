<?php

namespace App\Http\Controllers\Central;

use App\Http\Requests\Professor\StoreProfessoresRequest;
use App\Http\Requests\Professor\UpdateProfessoresRequest;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Professor;
use App\Models\Central\Turma;
use App\Models\Central\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ProfessorController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Professor::class);

        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::select(['id', 'user_id', 'especialidade', 'nivel_academico', 'created_at'])
            ->with(['user:id,nome,telefone'])
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return Inertia::render('professores/index', [
            'professores' => $professores,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Professor::class);

        return Inertia::render('professores/create');
    }

    public function store(StoreProfessoresRequest $request)
    {
        $this->authorize('create', Professor::class);

        $request->validated();

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'bi' => $request->bi,
            'telefone' => $request->telefone,
            'password' => Hash::make('123456'),
            'instituicao_id' => Auth::user()->instituicao_id,
        ]);

        $role = Role::where('name', 'Professor')->firstOrFail();

        $user->assignRole($role);

        Professor::create([
            'user_id' => $user->id,
            'especialidade' => $request->especialidade,
            'nivel_academico' => $request->nivel_academico,
        ]);

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor criado com sucesso.',
        ]);
    }

    public function show(Professor $professor)
    {
        $this->authorize('view', $professor);

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        $professor->load([
            'user:id,nome,email,bi,telefone',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
            'cursosTutelados.instituicaoCurso.curso:id,nome',
        ]);

        $cursos = $professor->cursosTutelados->map(function ($ct) {
            $curso = $ct->instituicaoCurso?->curso;
            if (! $curso) {
                return null;
            }

            return ['id' => $curso->id, 'nome' => $curso->nome];
        })->filter()->unique('id')->values();

        $turmas = Turma::with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id))
            ->where('ano_lectivo_id', $anoLectivoId)   // ← direto na turma
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
            'anoLectivoId' => $anoLectivoId,       // ← adicionado
            'anosLectivos' => AnoLectivo::all(),
        ]);
    }

    public function edit(Professor $professor)
    {
        $this->authorize('update', $professor);

        return Inertia::render('professores/edit', [
            'professor' => $professor->load('user:id,nome,email,bi,telefone'),
        ]);
    }

    public function update(UpdateProfessoresRequest $request, Professor $professor)
    {
        $this->authorize('update', $professor);

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
            'nivel_academico' => $request->nivel_academico,
        ]);

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor atualizado com sucesso.',
        ]);
    }

    public function destroy(Professor $professor)
    {
        $this->authorize('delete', $professor);

        $professor->delete($professor->id);

        return to_route('professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor removido com sucesso.',
        ]);
    }
}
